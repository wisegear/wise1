@extends('layouts.app')

@section('content')

<div class="mx-auto max-w-7xl px-4 md:py-12">

    {{-- Hero / summary card --}}
    <section class="relative z-0 overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center">
        @include('partials.hero-background')
        <div class="max-w-4xl">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Mortgage Affordability Calculator</h1>
            <p class="text-sm text-zinc-600 pt-2">Knowing whether you can afford a mortgage from a lenders perspective can be quite tricky, unless you have spent many years in different lenders making those decision, which I have.
                To assist those looking to be better prepared, I have create a simplfied version of a calculator that looks at the key elements of mortgage affordability from the lenders perspective.
            </p>
            <p class="text-sm pt-2 font-semibold italic text-amber-700">Importantly, each lender is different, don't assume just because it says you can afford it using this tool, that a lender will agree.  They all have
                their own tools and forumulas.  Equally, don't assume that just because this tool says you can't afford it, that a lender won't lend to you.  This is a guide only.
            </p>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/affordability.jpg') }}" alt="Mortgage Affordability" class="w-64 h-auto">
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

        <div class="space-y-8">
            <section class="rounded-lg border border-gray-200 bg-white p-5 md:p-6 shadow-sm">
                <div>
                    <h2 class="text-lg md:text-xl font-semibold text-gray-900">Property Details</h2>
                    <p class="text-sm text-zinc-600 mt-1">Enter the basics of the property and mortgage you are planning.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-5">
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
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 md:p-6 shadow-sm">
                <div>
                    <h2 class="text-lg md:text-xl font-semibold text-gray-900">Income Details</h2>
                    <p class="text-sm text-zinc-600 mt-1">Include all regular income. Use <span class="font-semibold">gross annual</span> for total before tax and <span class="font-semibold">net annual</span> for take‑home after tax and NI.</p>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-5">
                    <div class="rounded-md border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Applicant 1 (required)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    </div>

                    <div class="rounded-md border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Applicant 2 (optional)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 md:p-6 shadow-sm">
                <div>
                    <h2 class="text-lg md:text-xl font-semibold text-gray-900">Financial Commitments</h2>
                    <p class="text-sm text-zinc-600 mt-1">Add regular, ongoing commitments that lenders consider in affordability.</p>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-5">
                    <div class="rounded-md border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Loans & Hire Purchase</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    </div>

                    <div class="rounded-md border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Other Regular Outgoings</h3>
                        <div>
                            <label for="commit_other_monthly" class="block text-sm font-medium text-gray-700 mb-1">Other Regular Outgoings – Monthly (£)</label>
                            <input type="text" inputmode="numeric" data-format="currency" name="commit_other_monthly" id="commit_other_monthly" class="w-full rounded-md border border-zinc-400 p-2 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="e.g. 200" value="{{ old('commit_other_monthly', $prefill['commit_other_monthly'] ?? '') }}">
                            @error('commit_other_monthly')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="inner-button">
                View Results
            </button>
        </div>
    </form>

</div>

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

@endsection
