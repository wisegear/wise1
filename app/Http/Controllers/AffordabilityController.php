<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AffordabilityController extends Controller
{
    public function index(Request $request)
    {
        $token = $request->query('token');
        $prefill = [];
        if ($token) {
            $prefill = Cache::get('affordability:' . $token, []);
        }
        return view('affordability.index', ['prefill' => $prefill, 'token' => $token]);
    }

    public function show(string $token)
    {
        $results = Cache::get('affordability:' . $token);
        if (!$results) {
            return redirect()->route('affordability.index')
                ->with('warning', 'No results to display. Please complete the form.');
        }
        return view('affordability.show', ['data' => $results, 'token' => $token]);
    }

    public function calculate(Request $request)
    {
        // 1) Normalise currency-like inputs so numeric validation succeeds even with commas/spaces
        foreach ([
            'property_value','loan_amount','app1_gross_annual','app1_net_annual',
            'app2_gross_annual','app2_net_annual','commit_loans_hp_monthly','commit_credit_cards_balance',
            'commit_other_monthly'
        ] as $field) {
            if ($request->filled($field)) {
                $request->merge([$field => str_replace([',', ' '], '', $request->input($field))]);
            }
        }

        // 2) Minimal validation for the current form fields (bare-bones)
        $validated = $request->validate([
            'property_value' => 'required|numeric',
            'loan_amount' => 'required|numeric',
            'term_years' => 'required|integer|min:1|max:40',
            'mortgage_type' => 'required|string|in:repayment,interest_only',
            'app1_gross_annual' => 'required|numeric',
            'app1_net_annual' => 'required|numeric',
            'app2_gross_annual' => 'nullable|numeric',
            'app2_net_annual' => 'nullable|numeric',
            'commit_loans_hp_monthly' => 'nullable|numeric',
            'commit_credit_cards_balance' => 'nullable|numeric',
            'commit_other_monthly' => 'nullable|numeric',
        ]);

        // 3) Calculate combined income
        $gross1 = (float) ($validated['app1_gross_annual'] ?? 0);
        $gross2 = (float) ($validated['app2_gross_annual'] ?? 0);
        $net1Annual = (float) ($validated['app1_net_annual'] ?? 0);
        $net2Annual = (float) ($validated['app2_net_annual'] ?? 0);

        $validated['total_gross_income'] = $gross1 + $gross2;        // annual
        $validated['total_net_income']   = ($net1Annual + $net2Annual) / 12; // store monthly

        // 3b) Commitments totals
        $loansHpMonthly  = (float) ($validated['commit_loans_hp_monthly'] ?? 0);
        $ccBalance       = (float) ($validated['commit_credit_cards_balance'] ?? 0);
        $otherMonthly    = (float) ($validated['commit_other_monthly'] ?? 0);

        // Loans & HP
        $validated['loans_hp_monthly_total'] = $loansHpMonthly;                // monthly cost
        $validated['loans_hp_annual_total']  = $loansHpMonthly * 12;           // annualised cost

        // Credit cards (3% of balance per month)
        $validated['credit_cards_balance_total'] = $ccBalance;                  // total balances not cleared
        $validated['credit_cards_monthly_cost']  = $ccBalance * 0.03;           // assumed monthly cost
        $validated['credit_cards_annual_cost']   = $validated['credit_cards_monthly_cost'] * 12; // annualised

        // Other regular outgoings
        $validated['other_outgoings_monthly_total'] = $otherMonthly;           // monthly
        $validated['other_outgoings_annual_total']  = $otherMonthly * 12;      // annualised

        // 3c) Stress the applied-for mortgage at +2% above base (4.5% → 6.5%) and assess affordability
        $appliedLoan   = (float) ($validated['loan_amount'] ?? 0);
        $termYears     = (int)   ($validated['term_years'] ?? 0);
        $mortgageType  =          ($validated['mortgage_type'] ?? 'repayment');

        $stressAnnualRate = 0.065;              // 6.5%
        $rm = $stressAnnualRate / 12;           // monthly rate
        $nm = max(1, $termYears * 12);          // number of months, guard 0

        if ($mortgageType === 'interest_only') {
            $stressedMonthly = $appliedLoan * $rm; // IO: interest-only interest
        } else {
            // Repayment: P * r / (1 - (1+r)^-n)
            $den = (1 - pow(1 + $rm, -$nm));
            $stressedMonthly = $den > 0 ? ($appliedLoan * $rm / $den) : 0;
        }
        $stressedAnnual = $stressedMonthly * 12;

        $validated['stressed_monthly'] = $stressedMonthly;
        $validated['stressed_annual']  = $stressedAnnual;

        // Total monthly and annual costs including stressed new mortgage
        $otherMonthlyTotal = (
            ($validated['loans_hp_monthly_total'] ?? 0) +
            ($validated['credit_cards_monthly_cost'] ?? 0) +
            ($validated['other_outgoings_monthly_total'] ?? 0)
        );

        $validated['total_monthly_costs'] = $stressedMonthly + $otherMonthlyTotal;
        $validated['total_annual_costs']  = $validated['total_monthly_costs'] * 12;

        // Affordability check: net monthly income vs total monthly costs
        $netMonthlyIncome = (float) ($validated['total_net_income'] ?? 0);
        $validated['affordability_pass'] = $netMonthlyIncome > $validated['total_monthly_costs'];

        // Affordability scenarios at multiple rates (4.5%, 5.5%, 6.5%, 7.5%)
        $rates = [0.045, 0.055, 0.065, 0.075];
        $scenarios = [];
        foreach ($rates as $annualRate) {
            $rm_s = $annualRate / 12; // monthly rate for this scenario

            if ($mortgageType === 'interest_only') {
                $monthlyPayment = $appliedLoan * $rm_s;
            } else {
                // Repayment (annuity): P * r / (1 - (1+r)^-n)
                $den_s = (1 - pow(1 + $rm_s, -$nm));
                $monthlyPayment = $den_s > 0 ? ($appliedLoan * $rm_s / $den_s) : 0;
            }

            $totalMonthlyAtRate = $monthlyPayment + $otherMonthlyTotal; // include commitments

            $scenarios[] = [
                'rate'                => $annualRate,                 // e.g. 0.045
                'monthly_payment'     => $monthlyPayment,             // mortgage only at this rate
                'other_monthly_costs' => $otherMonthlyTotal,          // commitments constant
                'total_monthly_costs' => $totalMonthlyAtRate,         // mortgage + commitments
                'net_monthly_income'  => $netMonthlyIncome,           // from inputs
                'affordable'          => $netMonthlyIncome > $totalMonthlyAtRate,
            ];
        }
        $validated['affordability_scenarios'] = $scenarios;

        // LTI ratio = applied loan / total gross income (annual)
        $totalGrossIncome = (float) ($validated['total_gross_income'] ?? 0);
        $lti = $totalGrossIncome > 0 ? $appliedLoan / $totalGrossIncome : null;
        $validated['lti_ratio'] = $lti;
        $validated['lti_pass']  = is_null($lti) ? false : ($lti < 4.5);

        // 4) Persist results with a token and redirect
        $token = (string) Str::uuid();
        Cache::put('affordability:' . $token, $validated, now()->addDays(30));
        return redirect()->route('affordability.show', ['token' => $token]);
    }
}
