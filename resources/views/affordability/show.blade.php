@extends('layouts.app')

@section('content')

<div class="mx-auto max-w-7xl px-4 md:py-12">

    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-4xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Affordability Outcomes</h1>
            <p class="text-sm text-zinc-600 pt-2">To be clear again, this is a simplified view only.  It cannot take account of every lenders policy requirements.
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/mortgage_affordability.svg') }}" alt="EPC Dashboard" class="w-64 h-auto">
        </div>
    </section>

    {{-- Property Summary Panel --}}
    @php
        $value = (float) ($data['property_value'] ?? 0);
        $loan  = (float) ($data['loan_amount'] ?? 0);
        $termY = (int)   ($data['term_years'] ?? 0);
        $type  =          ($data['mortgage_type'] ?? 'repayment');

        $ltv = $value > 0 ? ($loan / $value) * 100 : null;

        // Fixed 4.5% interest rate
        $annualRate = 0.045;
        $r = $annualRate / 12; // monthly rate
        $n = max(1, $termY * 12); // number of months

        if ($type === 'interest_only') {
            $monthly = $loan * $r;
        } else {
            $den = (1 - pow(1 + $r, -$n));
            $monthly = $den > 0 ? ($loan * $r / $den) : 0;
        }

        $annual = $monthly * 12;

        $money = fn($num, $dec = 0) => '£' . number_format((float)$num, $dec);
    @endphp

    {{-- Income Summary Panel --}}
    @php
        $totalGross = isset($data['total_gross_income'])
            ? (float)$data['total_gross_income']
            : ((float)($data['app1_gross_annual'] ?? 0) + (float)($data['app2_gross_annual'] ?? 0));

        $totalNet = isset($data['total_net_income'])
            ? (float)$data['total_net_income']
            : ((float)($data['app1_net_monthly'] ?? 0) + (float)($data['app2_net_monthly'] ?? 0));
    @endphp

    {{-- Commitments Summary Panel --}}
    @php
        $mortBal  = (float) ($data['mortgages_balance_total'] ?? 0);
        $mortMon  = (float) ($data['mortgages_monthly_total'] ?? 0);
        $mortAnn  = (float) ($data['mortgages_annual_total'] ?? 0);
        $loanMon  = (float) ($data['loans_hp_monthly_total'] ?? 0);
        $loanAnn  = (float) ($data['loans_hp_annual_total'] ?? 0);
        $ccBal    = (float) ($data['credit_cards_balance_total'] ?? 0);
        $ccMon    = (float) ($data['credit_cards_monthly_cost'] ?? 0);
        $ccAnn    = (float) ($data['credit_cards_annual_cost'] ?? 0);
        $othMon   = (float) ($data['other_outgoings_monthly_total'] ?? 0);
        $othAnn   = (float) ($data['other_outgoings_annual_total'] ?? 0);
    @endphp

    {{-- Combined Summary Panel --}}
    <section class="rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b border-zinc-300 pb-2">Your Details (Review)</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Property -->
            <div>
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Property</h3>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-zinc-600">Property Value</dt>
                        <dd class="font-semibold">{{ $money($value, 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-600">Loan Amount</dt>
                        <dd class="font-semibold">{{ $money($loan, 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-600">Loan to Value (LTV)</dt>
                        <dd class="font-semibold">{{ is_null($ltv) ? '—' : number_format($ltv, 1) . '%' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-600">Estimated Payment @ 4.5%</dt>
                        <dd class="font-semibold">{{ $money($monthly, 2) }} <span class="text-zinc-600 font-normal">/ month</span></dd>
                        <dd class="text-zinc-600">{{ $money($annual, 2) }} per year · Term {{ $termY }} years · {{ $type === 'interest_only' ? 'Interest Only' : 'Repayment' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Income -->
            <div>
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Income</h3>
                @php $grossMonthly = $totalGross / 12; $netAnnual = $totalNet * 12; @endphp
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-zinc-600">Total Gross Income</dt>
                        <dd class="font-semibold">{{ $money($grossMonthly, 0) }} <span class="text-zinc-600 font-normal">/ month</span></dd>
                        <dd class="text-zinc-600">{{ $money($totalGross, 0) }} per year</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-600">Total Net Income</dt>
                        <dd class="font-semibold">{{ $money($totalNet, 0) }} <span class="text-zinc-600 font-normal">/ month</span></dd>
                        <dd class="text-zinc-600">{{ $money($netAnnual, 0) }} per year</dd>
                    </div>
                </dl>
            </div>

            <!-- Commitments -->
            <div>
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Commitments</h3>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-zinc-600">Loans & HP</dt>
                        <dd class="font-semibold">{{ $money($loanMon, 2) }} <span class="text-zinc-600 font-normal">/ month</span></dd>
                        <dd class="text-zinc-600">{{ $money($loanAnn, 2) }} per year</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-600">Credit Cards</dt>
                        <dd class="font-semibold">Balance: {{ $money($ccBal, 0) }}</dd>
                        <dd class="text-zinc-600">Estimated Cost: {{ $money($ccMon, 2) }} / month · {{ $money($ccAnn, 2) }} per year (3% assumption)</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-600">Other Regular Outgoings</dt>
                        <dd class="font-semibold">{{ $money($othMon, 2) }} <span class="text-zinc-600 font-normal">/ month</span></dd>
                        <dd class="text-zinc-600">{{ $money($othAnn, 2) }} per year</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- Results & Affordability Assessment --}}
    @php
        $stressedMonthly = (float) ($data['stressed_monthly'] ?? 0);
        $stressedAnnual  = (float) ($data['stressed_annual'] ?? 0);
        $totalMonthly    = (float) ($data['total_monthly_costs'] ?? 0);
        $totalAnnual     = (float) ($data['total_annual_costs'] ?? 0);
        $affordable      = $data['affordability_pass'] ?? false;
        $ltiRatio        = (float) ($data['lti_ratio'] ?? 0);
        $ltiPass         = $data['lti_pass'] ?? false;
    @endphp

    <section class="rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b border-zinc-300 pb-2">Affordability Outcome</h2>
        <p class="text-zinc-600 text-sm">The results below focus on two elements. Whether the mortgage requested is affordable and whether it meets the standard Loan 
            to Income threshold.  As at Oct 2025 the average best rate in the market is 4.5%.  Lenders stress mortgages but use different rates for different reasons.  Some may
            use 1%, others 2% and some still use 3%.  I have tested on all of these for the illustration below.  What this tells you is whether you can meet affordability
            at different stress rates a lender may apply.  The % shows how much of the total net income was used to either pass affordability or fail if above 100%.
        </p>
        <p class="text-zinc-600 text-sm pt-2">
            For LTI I have set this at 4.5 which is quite standard, that is, the mortgage you are applying for should be no more than 4.5 times your gross income.  If you
            know your lender has a higher LTI then you can still tell if it will work.
        </p>

        @php $scenarios = $data['affordability_scenarios'] ?? []; @endphp
        @if (!empty($scenarios))
            <div class="mt-8">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Affordability at Different Rates</h3>
                <div class="overflow-x-auto border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Rate</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Mortgage Payment</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Total Monthly Costs</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Is it affordable?</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">LTI pass?</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($scenarios as $s)
                                @php
                                    $ratePct = number_format(($s['rate'] ?? 0) * 100, 1) . '%';
                                    $mortMo  = $money($s['monthly_payment'] ?? 0, 2);
                                    $totalMo = $money($s['total_monthly_costs'] ?? 0, 2);
                                    $ok      = !empty($s['affordable']);
                                    $netMonthly = $s['net_monthly_income'] ?? 0;
                                    $totalCosts = $s['total_monthly_costs'] ?? 0;
                                    $incomeUsedPct = $netMonthly > 0 ? ($totalCosts / $netMonthly) * 100 : 0;
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $ratePct }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $mortMo }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $totalMo }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-800 font-medium">{{ number_format($incomeUsedPct, 1) }}%</span>
                                            @if ($ok)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Pass</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Fail</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        {{ number_format($ltiRatio, 2) }}
                                        @if ($ltiPass)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Pass</span>
                                        @else
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Fail</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>

    <div class="mt-6">
        <a href="{{ route('affordability.index', ['token' => $token ?? null]) }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">← Back to form</a>
    </div>

@endsection