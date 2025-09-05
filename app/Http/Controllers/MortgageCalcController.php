<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MortgageCalcController extends Controller
{
    public function index(Request $request)
    {
        // Show empty form on GET
        if (!$request->isMethod('post')) {
            return view('mortgagecalc.index');
        }

        // Validate inputs
        $validated = $request->validate([
            'amount' => ['required','string'], // allow commas; normalize below
            'term'   => ['required','integer','min:1','max:50'],
            'rate'   => ['required','numeric','min:0','max:100'],
        ]);

        // Normalize values (strip commas/spaces)
        $amountRaw = (string) $validated['amount'];
        $amount    = (int) str_replace([',',' '], '', $amountRaw);
        $termYears = (int) $validated['term'];
        $ratePct   = (float) $validated['rate'];

        // Guard against zero/invalid amount
        if ($amount <= 0) {
            return back()->withErrors(['amount' => 'Please enter a valid mortgage amount.'])->withInput();
        }

        // Calculations
        $n  = $termYears * 12;                 // total months
        $r  = $ratePct / 100 / 12;             // monthly rate

        // Monthly repayment (annuity formula). Handle r = 0 edge case
        $repaymentMonthly = $r == 0
            ? $amount / max($n, 1)
            : ($amount * $r) / (1 - pow(1 + $r, -$n));

        // Interest-only monthly (simple interest)
        $interestOnlyMonthly = ($amount * ($ratePct / 100)) / 12;

        // Optional: annuals
        $repaymentAnnual    = $repaymentMonthly * 12;
        $interestOnlyAnnual = $interestOnlyMonthly * 12;

        // Example extras if you want to surface these later
        $stress_rate = 3; // pct
        $svr = 4;         // pct

        $result = [
            'amount'               => $amount,
            'term_years'           => $termYears,
            'rate_pct'             => $ratePct,
            'repayment_monthly'    => $repaymentMonthly,
            'repayment_annual'     => $repaymentAnnual,
            'interest_only_monthly'=> $interestOnlyMonthly,
            'interest_only_annual' => $interestOnlyAnnual,
            'stress_rate'          => $stress_rate,
            'svr'                  => $svr,
        ];

        // Return back to the same page with results & keep original (formatted) inputs
        return view('mortgagecalc.index', [
            'result' => $result,
            'input'  => [
                'amount' => $amountRaw,
                'term'   => $termYears,
                'rate'   => $ratePct,
            ],
        ]);
    }
}
