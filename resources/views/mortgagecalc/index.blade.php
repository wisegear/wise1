@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 md:py-12">
    {{-- Hero / summary card --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-4xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Mortgage Calculator</h1>
            <p class="mt-2 text-sm leading-6 text-gray-700">
                <span class="text-zinc-600">Calculate mortgage payments for repayment and interest only mortgages.  Select term, interest rate and get information that factors
                    in a lender stress rate to see the potential impact on payments.</span><br>
                </span>
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/mortgage-calculator.svg') }}" alt="Mortgage-Caclulator" class="w-42 h-auto">
        </div>
</section>    

    {{-- Calculator form panel --}}
    <section class="rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Calculate Your Mortgage</h2>
        <form method="POST" action="{{ route('mortgagecalc.index') }}" class="space-y-4">
            @csrf

            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label for="amount" class="block text-sm font-medium text-gray-700">Mortgage Amount (GBP)</label>
                    <input type="text" name="amount" id="amount" placeholder="e.g. 250,000" class="p-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm" required>
                </div>

                <div class="flex-1">
                    <label for="term" class="block text-sm font-medium text-gray-700">Term (years)</label>
                    <input type="number" name="term" id="term" placeholder="e.g. 25" min="1" class="p-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm" required>
                </div>

                <div class="flex-1">
                    <label for="rate" class="block text-sm font-medium text-gray-700">Interest Rate (%)</label>
                    <input type="number" name="rate" id="rate" placeholder="e.g. 5.5" step="0.01" min="0" class="p-2 mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm" required>
                </div>
            </div>

            <div>
                <button type="submit" class="inner-button">
                    Calculate
                </button>
            </div>
        </form>
    </section>

    <!-- Form results -->
    <div class="">
@if(!empty($result))
<section class="rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm">
  <h3 class="text-lg font-semibold text-gray-900">Results</h3>
  <p class="text-sm text-zinc-500 mb-6">The results below show payment for both a repayment and interest only mortgage with charts demonstrating how the mortgage is paid off,
    or not if interest only.  Many mortgage lenders still use a stress rate in their affordability calculations, the bottom panel shows what the impact would be if a stress rate of
    <span class="text-rose-500">{{ rtrim(rtrim(number_format($result['stress_rate'], 2), '0'), '.') }}%</span> was added to the rate you entered of 
    <span class="text-rose-500">{{ rtrim(rtrim(number_format($result['rate_pct'], 2), '0'), '.') }}%</span>.
  </p>

  {{-- Input summary --}}
  <div class="grid sm:grid-cols-3 gap-4 text-sm mb-6 border rounded-lg p-4 text-center">
    <div class="flex flex-col items-center">
      <div class="text-gray-500">Amount</div>
      <div class="font-medium">£{{ number_format($result['amount']) }}</div>
    </div>
    <div class="flex flex-col items-center">
      <div class="text-gray-500">Term</div>
      <div class="font-medium">{{ $result['term_years'] }} years</div>
    </div>
    <div class="flex flex-col items-center">
      <div class="text-gray-500">Interest rate</div>
      <div class="font-medium">{{ rtrim(rtrim(number_format($result['rate_pct'], 2), '0'), '.') }}%</div>
    </div>
  </div>

  @php
    $nMonths = max(1, (int)($result['term_years'] ?? 0) * 12);
    $amountVal = (float)($result['amount'] ?? 0);
    $repayMonthly = (float)($result['repayment_monthly'] ?? 0);
    $ioMonthly = (float)($result['interest_only_monthly'] ?? 0);

    $repayTotal = $repayMonthly * $nMonths; // total paid over term
    $ioTotal    = $ioMonthly * $nMonths;    // total interest paid over term

    $perPoundRepay = $amountVal > 0 ? $repayTotal / $amountVal : 0; // total per £1 borrowed
    $perPoundIO    = $amountVal > 0 ? $ioTotal / $amountVal : 0;    // interest per £1 borrowed

    // Stress-rate calculations (rate + stress_rate)
    $stressPct   = (float)($result['stress_rate'] ?? 0);
    $baseRatePct = (float)($result['rate_pct'] ?? 0);
    $stressedRatePct = $baseRatePct + $stressPct;
    $rBase     = $baseRatePct / 100 / 12;
    $rStressed = $stressedRatePct / 100 / 12;

    // Repayment stressed monthly
    $repaymentMonthlyStressed = $rStressed == 0
        ? ($amountVal / $nMonths)
        : ($amountVal * $rStressed) / (1 - pow(1 + $rStressed, -$nMonths));

    // Interest-only stressed monthly
    $interestOnlyMonthlyStressed = ($amountVal * ($stressedRatePct / 100)) / 12;

    // Deltas (extra per month)
    $repaymentMonthlyExtra = max(0, $repaymentMonthlyStressed - $repayMonthly);
    $interestOnlyMonthlyExtra = max(0, $interestOnlyMonthlyStressed - $ioMonthly);
  @endphp

  <div class="grid md:grid-cols-2 gap-6">
    {{-- Repayment panel --}}
    <div class="rounded-lg border border-gray-200 bg-white p-5">
      <h4 class="text-base font-semibold text-gray-900 mb-3">Repayment Mortgage</h4>
      <dl class="grid grid-cols-3 gap-3 text-sm">
        <div>
          <dt class="text-gray-500">Monthly payment</dt>
          <dd class="font-medium">£{{ number_format($result['repayment_monthly'], 2) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Annual payment</dt>
          <dd class="font-medium">£{{ number_format($result['repayment_annual'], 2) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Total amount paid</dt>
          <dd class="font-medium">£{{ number_format($repayTotal, 2) }}</dd>
        </div>
      </dl>
      <p class="mt-3 text-xs text-gray-500">
        On a repayment basis the monthly payment pays the interest due and a portion of the capital borrowed.  The amount of capital reduces slowly at the start, eventually more
        capital is paid each month than interest.  Using a repayment mortgage and assuming all payments are made the full amount is repaid at the end of term.
      </p>
      <p class="mt-3 text-xs text-gray-500">For every <span class="text-rose-700">£1</span>  borrowed you repay <span class="text-rose-700">£{{ number_format($perPoundRepay, 2) }}</span> over the term.</p>
      <div class="mt-5">
        <h5 class="text-sm font-medium text-gray-700 mb-2">Balance over term</h5>
        <div class="relative h-56">
          <canvas id="repaymentChart" class="absolute inset-0 w-full h-full"></canvas>
        </div>
        <p class="mt-2 text-xs text-gray-500">Shows outstanding balance decreasing to £0 by the end of the term.</p>
      </div>
    </div>

    {{-- Interest-only panel --}}
    <div class="rounded-lg border border-gray-200 bg-white p-5">
      <h4 class="text-base font-semibold text-gray-900 mb-3">Interest-Only Mortgage</h4>
      <dl class="grid grid-cols-3 gap-3 text-sm">
        <div>
          <dt class="text-gray-500">Monthly interest</dt>
          <dd class="font-medium">£{{ number_format($result['interest_only_monthly'], 2) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Annual interest</dt>
          <dd class="font-medium">£{{ number_format($result['interest_only_annual'], 2) }}</dd>
        </div>
        <div>
          <dt class="text-gray-500">Total interest paid</dt>
          <dd class="font-medium">£{{ number_format($ioTotal, 2) }}</dd>
        </div>
      </dl>
      <p class="mt-3 text-xs text-gray-500">
        With interest only the payments cover the interest only, the capital is not reduced, throughout the term £{{ number_format($result['amount']) }} is always owed, unless additional
        payments are made throughout the term.  Lenders will expect proof of ability to repay the loan at the end of the term.  This could be through selling the property or using an
        acceptable asset at or before the term ends.
      </p>
      <p class="mt-3 text-xs text-gray-500">For every <span class="text-rose-700">£1</span> borrowed you pay <span class="text-rose-700">£{{ number_format($perPoundIO, 2) }}</span> over the term.</p>
      <div class="mt-5">
        <h5 class="text-sm font-medium text-gray-700 mb-2">Balance over term</h5>
        <div class="relative h-56">
          <canvas id="interestOnlyChart" class="absolute inset-0 w-full h-full"></canvas>
        </div>
        <p class="mt-2 text-xs text-gray-500">Shows outstanding balance remaining constant at the original loan amount.</p>
      </div>
    </div>
  </div>
  <!-- Stress rate impact -->
  <div class="text-smmt-8 rounded-lg border border-rose-600 p-5 mt-6">
    <h4 class="text-base font-semibold text-rose-600 mb-2">Stress Rate Impact</h4>
    <p class="text-sm text-zinc-700">
      Some lenders assess affordability by <em>stressing</em> the interest rate. Using a stress rate of
      <span class="font-semibold">{{ rtrim(rtrim(number_format($result['stress_rate'], 2), '0'), '.') }}%</span> on top of your entered rate of
      <span class="font-semibold">{{ rtrim(rtrim(number_format($result['rate_pct'], 2), '0'), '.') }}%</span> (total <span class="font-semibold">{{ rtrim(rtrim(number_format($stressedRatePct, 2), '0'), '.') }}%</span>).
      This is not the rate you will pay.  Lenders are demonstrating that if the rate was to increase, a borrower could still afford the payments.
    </p>
    <ul class="mt-3 text-sm text-zinc-700 space-y-1">
      <li>
        • <span class="font-medium">Repayment</span>: you would pay an extra
        <span class="font-semibold">£{{ number_format($repaymentMonthlyExtra, 2) }}</span> each month, increasing the monthly payment to
        <span class="font-semibold">£{{ number_format($repaymentMonthlyStressed, 2) }}</span>.
      </li>
      <li>
        • <span class="font-medium">Interest-only</span>: you would pay an extra
        <span class="font-semibold">£{{ number_format($interestOnlyMonthlyExtra, 2) }}</span> each month, increasing the monthly payment to
        <span class="font-semibold">£{{ number_format($interestOnlyMonthlyStressed, 2) }}</span>.
      </li>
    </ul>
  </div>
</section>
@endif
    </div>
@if(!empty($result))
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Ensure Chart.js is available
    if (typeof window.Chart === 'undefined') {
      console.warn('Chart.js not found: make sure it is included in your layout.');
      return;
    }

    const amount   = {{ (int) $result['amount'] }}; // principal in pence not needed; integer pounds is fine
    const years    = {{ (int) $result['term_years'] }};
    const ratePct  = {{ (float) $result['rate_pct'] }};

    const n = years * 12;                  // months
    const r = ratePct / 100 / 12;          // monthly rate

    // Build x labels by year (0..years)
    const labels = Array.from({ length: years + 1 }, (_, i) => i);

    // Repayment balance path (sampled yearly)
    const repaymentBalances = (() => {
      let bal = amount;
      const path = [bal]; // year 0
      if (r === 0) {
        // Straight-line principal reduction
        const annual = amount / years;
        for (let y = 1; y <= years; y++) {
          bal = Math.max(0, amount - annual * y);
          path.push(bal);
        }
        return path;
      }
      const payment = (amount * r) / (1 - Math.pow(1 + r, -n));
      for (let y = 1; y <= years; y++) {
        for (let m = 0; m < 12; m++) {
          const interest = bal * r;
          const principal = payment - interest;
          bal = Math.max(0, bal - principal);
        }
        path.push(bal);
      }
      // Force last value to zero for neatness
      path[path.length - 1] = 0;
      return path;
    })();

    // Interest-only balance path (constant)
    const ioBalances = Array.from({ length: years + 1 }, () => amount);

    // Helper to format pounds nicely
    const fmt = (v) => '£' + new Intl.NumberFormat('en-GB', { maximumFractionDigits: 0 }).format(v);

    // Create charts
    const repCtx = document.getElementById('repaymentChart');
    if (repCtx) {
      new Chart(repCtx, {
        type: 'bar',
        data: {
          labels: labels.map(y => (y === 1 ? '1 year' : y)),
          datasets: [{
            label: 'Outstanding balance',
            data: repaymentBalances,
            fill: false,
            tension: 0.15,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          scales: {
            y: {
              ticks: {
                callback: (v) => fmt(v)
              },
              beginAtZero: true,
            },
            x: {
              ticks: {
                maxRotation: 0,
                minRotation: 0
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: (ctx) => `${ctx.dataset.label}: ${fmt(ctx.parsed.y)}`
              }
            },
            legend: { display: false }
          }
        }
      });
    }

    const ioCtx = document.getElementById('interestOnlyChart');
    if (ioCtx) {
      new Chart(ioCtx, {
        type: 'bar',
        data: {
          labels: labels.map(y => (y === 1 ? '1 year' : y)),
          datasets: [{
            label: 'Outstanding balance',
            data: ioBalances,
            fill: false,
            tension: 0.15,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          scales: {
            y: {
              ticks: {
                callback: (v) => fmt(v)
              },
              beginAtZero: true,
            },
            x: {
              ticks: {
                maxRotation: 0,
                minRotation: 0
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: (ctx) => `${ctx.dataset.label}: ${fmt(ctx.parsed.y)}`
              }
            },
            legend: { display: false }
          }
        }
      });
    }
  });
</script>
@endif
</div>
@endsection
