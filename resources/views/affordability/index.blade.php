@extends('layouts.app')

@section('content')

<div class="mx-auto max-w-7xl px-4 md:py-12">

    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-4xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Mortgage Affordability Calculator</h1>
            <p class="text-sm text-zinc-600 pt-2">Knowing whether you can afford a mortgage from a lenders perspective can be quite tricky, unless you have spent many years in different lenders making those decision, which I have.
                To assist those looking to be better prepared, I have create a simplfied version of a calculator that looks at the key elements of mortgage affordability from the lenders perspective.
            </p>
            <p class="text-sm pt-2 font-semibold italic text-rose-700">Importantly, each lender is different, don't assume just because it says you can afford it using this tool, that a lender will agree.  They all have
                their own tools and forumulas.
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/mortgage_affordability.svg') }}" alt="Mortgage Affordability" class="w-64 h-auto">
        </div>
    </section>

    <!-- Affordability form -->

    <form id="affordability-form" action="{{ route('affordability.calculate') }}" method="POST" class="space-y-8 bg-white/80 border border-gray-200 shadow-sm rounded-lg p-6 md:p-8">
        @csrf
        @if ($errors->any())
            <div class="rounded-md border border-rose-200 bg-rose-50 p-4 text-rose-800 text-sm">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Section 1: Property Details --}}
        <details id="sec-property" class="rounded-lg border border-gray-200 bg-white/90 shadow-sm md:p-1" open>
            <summary class="list-none cursor-pointer select-none p-4 md:p-5 flex items-center justify-between">
                <span class="flex items-center gap-2 md:gap-3">
                    <span class="text-lg md:text-xl font-semibold text-gray-900">Property Details</span>
                    <span id="status-property" class="inline-block h-3 w-3 md:h-4 md:w-4 rounded-full bg-amber-500" title="Incomplete"></span>
                    <span id="label-property" class="ml-2 text-xs md:text-sm font-medium text-amber-600">Incomplete</span>
                </span>
                <svg class="w-4 h-4 text-zinc-500 transition-transform duration-200 details-toggle" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.27a.75.75 0 01-.02-1.06z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="px-4 md:px-6 pb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Property Value -->
                    <div>
                        <label for="property_value" class="block text-sm font-medium text-gray-700 mb-1">Property Value (£)</label>
                        <input type="text" inputmode="numeric" data-format="currency" name="property_value" id="property_value" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 300,000" required value="{{ old('property_value', $prefill['property_value'] ?? '') }}">
                        @error('property_value')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Loan Amount -->
                    <div>
                        <label for="loan_amount" class="block text-sm font-medium text-gray-700 mb-1">Loan Amount (£)</label>
                        <input type="text" inputmode="numeric" data-format="currency" name="loan_amount" id="loan_amount" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 250,000" required value="{{ old('loan_amount', $prefill['loan_amount'] ?? '') }}">
                        @error('loan_amount')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Term in Years -->
                    <div>
                        <label for="term_years" class="block text-sm font-medium text-gray-700 mb-1">Term (Years)</label>
                        <input type="number" name="term_years" id="term_years" min="1" max="40" step="1" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 25" required value="{{ old('term_years', $prefill['term_years'] ?? '') }}">
                        @error('term_years')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mortgage Type -->
                    <div>
                        <label for="mortgage_type" class="block text-sm font-medium text-gray-700 mb-1">Type of Mortgage</label>
                        <select name="mortgage_type" id="mortgage_type" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" required>
                            <option value="">Select type</option>
                            <option value="repayment" {{ (old('mortgage_type', $prefill['mortgage_type'] ?? '')==='repayment') ? 'selected' : '' }}>Repayment</option>
                            <option value="interest_only" {{ (old('mortgage_type', $prefill['mortgage_type'] ?? '')==='interest_only') ? 'selected' : '' }}>Interest Only</option>
                        </select>
                        @error('mortgage_type')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </details>

        {{-- Section 2: Income Details --}}
        <details id="sec-income" class="rounded-lg border border-gray-200 bg-white/90 shadow-sm md:p-1 mt-4">
            <summary class="list-none cursor-pointer select-none p-4 md:p-5 flex items-center justify-between">
                <span class="flex items-center gap-2 md:gap-3">
                    <span class="text-lg md:text-xl font-semibold text-gray-900">Income Details</span>
                    <span id="status-income" class="inline-block h-3 w-3 md:h-4 md:w-4 rounded-full bg-amber-500" title="Incomplete"></span>
                    <span id="label-income" class="ml-2 text-xs md:text-sm font-medium text-amber-600">Incomplete</span>
                </span>
                <svg class="w-4 h-4 text-zinc-500 transition-transform duration-200 details-toggle" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.27a.75.75 0 01-.02-1.06z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="px-4 md:px-6 pb-6">
                <p class="text-sm text-zinc-600 mb-4">Include all regular income such as salary, bonuses, commission, overtime, allowances, and verified benefits. Use <span class="font-semibold">gross annual</span> for total before tax, and <span class="font-semibold">net annual</span> for take‑home after tax and NI.</p>
                <div class="grid grid-cols-1 gap-8">
                    <fieldset class="border border-gray-200 rounded-md p-4">
                        <legend class="px-2 text-sm font-semibold text-gray-800">Applicant 1 (required)</legend>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                            <div>
                                <label for="app1_gross_annual" class="block text-sm font-medium text-gray-700 mb-1">Gross Annual Income (£)</label>
                                <input type="text" inputmode="numeric" data-format="currency" name="app1_gross_annual" id="app1_gross_annual" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 60,000" required value="{{ old('app1_gross_annual', $prefill['app1_gross_annual'] ?? '') }}">
                                @error('app1_gross_annual')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="app1_net_annual" class="block text-sm font-medium text-gray-700 mb-1">Net Annual Income (£)</label>
                                <input type="text" inputmode="numeric" data-format="currency" name="app1_net_annual" id="app1_net_annual" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 36,000" required value="{{ old('app1_net_annual', $prefill['app1_net_annual'] ?? '') }}">
                                @error('app1_net_annual')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border border-gray-200 rounded-md p-4">
                        <legend class="px-2 text-sm font-semibold text-gray-800">Applicant 2 (optional)</legend>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                            <div>
                                <label for="app2_gross_annual" class="block text-sm font-medium text-gray-700 mb-1">Gross Annual Income (£)</label>
                                <input type="text" inputmode="numeric" data-format="currency" name="app2_gross_annual" id="app2_gross_annual" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 40,000" value="{{ old('app2_gross_annual', $prefill['app2_gross_annual'] ?? '') }}">
                                @error('app2_gross_annual')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="app2_net_annual" class="block text-sm font-medium text-gray-700 mb-1">Net Annual Income (£)</label>
                                <input type="text" inputmode="numeric" data-format="currency" name="app2_net_annual" id="app2_net_annual" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 28,000" value="{{ old('app2_net_annual', $prefill['app2_net_annual'] ?? '') }}">
                                @error('app2_net_annual')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
        </details>

        {{-- Section 3: Financial Commitments --}}
        <details id="sec-commitments" class="rounded-lg border border-gray-200 bg-white/90 shadow-sm md:p-1 mt-4">
            <summary class="list-none cursor-pointer select-none p-4 md:p-5 flex items-center justify-between">
                <span class="flex items-center gap-2 md:gap-3">
                    <span class="text-lg md:text-xl font-semibold text-gray-900">Financial Commitments</span>
                    <span id="status-commitments" class="inline-block h-3 w-3 md:h-4 md:w-4 rounded-full bg-amber-500" title="Incomplete"></span>
                    <span id="label-commitments" class="ml-2 text-xs md:text-sm font-medium text-amber-600">Incomplete</span>
                </span>
                <svg class="w-4 h-4 text-zinc-500 transition-transform duration-200 details-toggle" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.27a.75.75 0 01-.02-1.06z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="px-4 md:px-6 pb-6">
                <p class="text-sm text-zinc-600 mb-4">Add regular, ongoing commitments that lenders consider in affordability. Use totals where applicable.</p>
                <div class="grid grid-cols-1 gap-8">

                    <fieldset class="border border-gray-200 rounded-md p-4">
                        <legend class="px-2 text-sm font-semibold text-gray-800">Loans & Hire Purchase</legend>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                            <div>
                                <label for="commit_loans_hp_monthly" class="block text-sm font-medium text-gray-700 mb-1">Loans / HP – Total Monthly Cost (£)</label>
                                <input type="text" inputmode="numeric" data-format="currency" name="commit_loans_hp_monthly" id="commit_loans_hp_monthly" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 300" value="{{ old('commit_loans_hp_monthly', $prefill['commit_loans_hp_monthly'] ?? '') }}">
                                @error('commit_loans_hp_monthly')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="commit_credit_cards_balance" class="block text-sm font-medium text-gray-700 mb-1">Credit Cards – Balance Not Cleared Monthly (£)</label>
                                <input type="text" inputmode="numeric" data-format="currency" name="commit_credit_cards_balance" id="commit_credit_cards_balance" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 2,500" value="{{ old('commit_credit_cards_balance', $prefill['commit_credit_cards_balance'] ?? '') }}">
                                @error('commit_credit_cards_balance')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="border border-gray-200 rounded-md p-4">
                        <legend class="px-2 text-sm font-semibold text-gray-800">Other Regular Outgoings</legend>
                        <div class="grid grid-cols-1 gap-6 mt-2">
                            <div>
                                <label for="commit_other_monthly" class="block text-sm font-medium text-gray-700 mb-1">Other Regular Outgoings – Monthly (£)</label>
                                <input type="text" inputmode="numeric" data-format="currency" name="commit_other_monthly" id="commit_other_monthly" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 200" value="{{ old('commit_other_monthly', $prefill['commit_other_monthly'] ?? '') }}">
                                @error('commit_other_monthly')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
        </details>

        <div class="flex justify-end">
            <button type="submit" class="inner-button">
                View Results
            </button>
        </div>
    </form>

</div>

<script>
  document.addEventListener('DOMContentLoaded', function(){
    const groups = [
      document.getElementById('sec-property'),
      document.getElementById('sec-income'),
      document.getElementById('sec-commitments')
    ].filter(Boolean);

    // rotate chevrons
    const syncChevron = (det) => {
      const icon = det.querySelector('.details-toggle');
      if (!icon) return;
      icon.style.transform = det.open ? 'rotate(180deg)' : 'rotate(0deg)';
    };

    groups.forEach(d => {
      syncChevron(d);
      d.addEventListener('toggle', () => {
        syncChevron(d);
        // Close others when one opens
        if (d.open) {
          groups.forEach(o => { if (o !== d) o.open = false; });
        }
      });
    });
  });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formatNumber = (digits) => {
        if (!digits) return '';
        // Remove leading zeros
        digits = digits.replace(/^0+(?=\d)/, '');
        return new Intl.NumberFormat('en-GB').format(Number(digits));
    };

    const handleInput = (e) => {
        const input = e.target;
        // only act on our currency fields
        if (!input.matches('[data-format="currency"]')) return;

        // remember cursor at end approach for stability
        const digitsOnly = input.value.replace(/[^0-9]/g, '');
        const formatted = formatNumber(digitsOnly);

        // If empty, allow empty
        input.value = formatted;

        // place caret at end (reliable and simple)
        const len = input.value.length;
        input.setSelectionRange(len, len);
    };

    // Attach to all currency-format inputs
    const currencyFields = Array.from(document.querySelectorAll('[data-format="currency"]'));

    currencyFields.forEach(field => {
        field.addEventListener('input', handleInput);
        field.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text') || '';
            const digits = pasted.replace(/[^0-9]/g, '');
            field.value = formatNumber(digits);
            const len = field.value.length;
            field.setSelectionRange(len, len);
        });
        // If there is a server-provided value, format it on load
        if (field.value) {
            const digits = field.value.replace(/[^0-9]/g, '');
            field.value = formatNumber(digits);
        }
    });

    // On submit: strip commas so server receives clean numbers
    const form = document.getElementById('affordability-form');
    if (form) {
        form.addEventListener('submit', () => {
            currencyFields.forEach(field => {
                field.value = field.value.replace(/,/g, '');
            });
        });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const val = id => {
    const el = document.getElementById(id);
    return el ? String(el.value).trim() : '';
  };

  // Set a specific color + tooltip on a status dot and update label
  const setDot = (id, color, title) => {
    const dot = document.getElementById(id);
    if (!dot) return;
    dot.classList.remove('bg-emerald-500','bg-amber-500','bg-rose-500');
    dot.classList.add(color);
    if (title) dot.setAttribute('title', title);

    // Also set the text label next to the dot
    const labelId = id.replace('status', 'label');
    const label = document.getElementById(labelId);
    if (label) {
      label.textContent = title || '';
      label.classList.remove('text-emerald-600','text-amber-600','text-rose-600');
      let textClass = 'text-zinc-600';
      if (color.includes('emerald')) textClass = 'text-emerald-600';
      else if (color.includes('amber')) textClass = 'text-amber-600';
      else if (color.includes('rose')) textClass = 'text-rose-600';
      label.classList.add(textClass);
    }
  };

  // Completion rules
  const propertyComplete = () => val('property_value') && val('loan_amount') && val('term_years') && val('mortgage_type');
  const incomeComplete    = () => val('app1_gross_annual') && val('app1_net_annual');
  const commitmentsAny    = () => val('commit_loans_hp_monthly') || val('commit_credit_cards_balance') || val('commit_other_monthly');

  const update = () => {
    // Property: required → green if complete, red if not
    if (propertyComplete()) {
      setDot('status-property', 'bg-emerald-500', 'Complete');
    } else {
      setDot('status-property', 'bg-rose-500', 'Incomplete');
    }

    // Income: required → green if complete, red if not
    if (incomeComplete()) {
      setDot('status-income', 'bg-emerald-500', 'Complete');
    } else {
      setDot('status-income', 'bg-rose-500', 'Incomplete');
    }

    // Commitments: optional → green if any provided, amber if none
    if (commitmentsAny()) {
      setDot('status-commitments', 'bg-emerald-500', 'Complete');
    } else {
      setDot('status-commitments', 'bg-amber-500', 'Optional');
    }
  };

  // Run once on load
  update();

  // Watch relevant fields
  [
    'property_value','loan_amount','term_years','mortgage_type',
    'app1_gross_annual','app1_net_annual',
    'commit_loans_hp_monthly','commit_credit_cards_balance','commit_other_monthly'
  ].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    ['input','change','blur'].forEach(ev => el.addEventListener(ev, update));
  });
});
</script>

@endsection