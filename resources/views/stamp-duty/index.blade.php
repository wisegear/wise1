@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">
    {{-- Hero / summary card --}}
    <section class="relative overflow-hidden rounded-lg border border-gray-200 bg-white/80 p-6 md:p-8 shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center">
        <div class="max-w-3xl">
            <div>
                <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-gray-900">Stamp Duty & Land Taxes Calculator</h1>
                <p class="mt-2 text-sm leading-6 text-gray-700">Calculate <strong>SDLT</strong> (England &amp; NI), <strong>LBTT</strong> (Scotland) and <strong>LTT</strong> (Wales), including first‑time buyer rules, second home / higher rates and SDLT non‑resident surcharge.</p>
            </div>
        </div>
        <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
            <img src="{{ asset('assets/images/site/calculator.svg') }}" alt="EPC Dashboard" class="w-64 h-auto">
        </div>
    </section>

    {{-- Two-column layout: form (left) + results (right) --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form panel --}}
        <div class="lg:col-span-1">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Inputs</h2>
                <form id="calc" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm mb-1" for="price">Property price (£)</label>
                     <input id="price" name="price" type="text" inputmode="numeric" pattern="[0-9,]*" class="w-full border rounded p-2" placeholder="e.g. 350,000" required>
                    </div>

                    <div>
                        <label class="block text-sm mb-1" for="region">Region</label>
                        <select id="region" name="region" class="w-full border rounded p-2">
                            <option value="eng-ni">England &amp; NI (SDLT)</option>
                            <option value="scotland">Scotland (LBTT)</option>
                            <option value="wales">Wales (LTT)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm mb-1" for="buyer_type">Buyer type</label>
                        <select id="buyer_type" name="buyer_type" class="w-full border rounded p-2">
                            <option value="main">Currently own a property</option>
                            <option value="first_time">First‑time buyer</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">If you also select “Second home”, first‑time buyer relief will be ignored automatically.</p>
                    </div>

                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="additional_property" name="additional_property" class="mr-2">
                            <span>Second home/additional property?</span>
                        </label>
                        <div id="nr-wrap" class="hidden">
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="non_resident" name="non_resident" class="mr-2">
                                <span>Non‑resident? (SDLT only)</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="w-full md:w-auto px-4 py-2 rounded bg-lime-600 hover:bg-lime-500 text-white cursor-pointer">Calculate</button>
                </form>
            </div>
        </div>

        {{-- Results panel --}}
        <div class="lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Results</h2>

                <div id="summary" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 hidden">
                    <div class="rounded border border-gray-200 p-4">
                        <div class="text-xs text-gray-500">Jurisdiction</div>
                        <div id="jurisdiction" class="text-sm mt-2 font-medium"></div>
                    </div>
                    <div class="rounded border border-gray-200 p-4">
                        <div class="text-xs text-gray-500">Total tax due</div>
                        <div id="total_tax" class="text-xl mt-2 font-semibold"></div>
                    </div>
                    <div class="rounded border border-gray-200 p-4">
                        <div class="text-xs text-gray-500">Base tax (before surcharges)</div>
                        <div id="base_tax" class="text-xl mt-2 font-semibold"></div>
                    </div>
                </div>

                <div id="badges" class="flex flex-wrap gap-2 mb-4 hidden"></div>

                <div class="overflow-x-auto">
                    <table id="bands_table" class="min-w-full border border-gray-200 text-sm hidden">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left border">Band from</th>
                                <th class="px-3 py-2 text-left border">Band to</th>
                                <th class="px-3 py-2 text-left border">Rate</th>
                                <th class="px-3 py-2 text-left border">Amount in band</th>
                                <th class="px-3 py-2 text-left border">Tax</th>
                            </tr>
                        </thead>
                        <tbody id="bands_body"></tbody>
                    </table>
                </div>

                <div id="surcharges_wrap" class="mt-6 hidden">
                    <h3 class="text-base font-medium text-gray-900 mb-2">Surcharges</h3>
                    <div class="overflow-x-auto">
                        <table id="surcharges_table" class="min-w-full border border-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left border">Label</th>
                                    <th class="px-3 py-2 text-left border">Rate</th>
                                    <th class="px-3 py-2 text-left border">Base amount</th>
                                    <th class="px-3 py-2 text-left border">Tax</th>
                                </tr>
                            </thead>
                            <tbody id="surcharges_body"></tbody>
                        </table>
                    </div>
                </div>


                <div id="placeholder" class="text-sm text-gray-600">Enter details on the left and hit <em>Calculate</em> to see the breakdown.</div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="lg:col-span-3 mt-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm text-sm text-gray-700">
            <h3 class="font-medium text-gray-900 mb-2">Notes</h3>
            <ul class="list-disc ml-5 space-y-1">
                <li>England &amp; NI: SDLT higher rates add <strong>+5%</strong> to the full price; non‑resident adds <strong>+2%</strong> (both cumulative).</li>
                <li>Scotland: LBTT ADS is <strong>8%</strong> on the full price; FTB nil‑rate band increased to £175,000.</li>
                <li>Wales: LTT has separate higher‑rates bands for additional properties; no FTB relief.</li>
                <li>This tool is a guide only and does not constitute advice.</li>
            </ul>
        </div>
    </section>
</div>

<script>
(function(){
  const fmtGBP = (n) => new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n);
  const el = (id) => document.getElementById(id);

  const regionSel = el('region');
  const nrWrap = el('nr-wrap');
  const nonResident = el('non_resident');

  function toggleNR() {
    const show = regionSel.value === 'eng-ni';
    nrWrap.classList.toggle('hidden', !show);
    if (!show) nonResident.checked = false;
  }
  regionSel.addEventListener('change', toggleNR);
  toggleNR();

  document.getElementById('calc').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const payload = {
      // Normalise comma-formatted price to a number
      price: Number((form.price.value || '').replace(/,/g, '')),
      region: form.region.value,
      buyer_type: form.buyer_type.value,
      additional_property: form.additional_property.checked,
      non_resident: form.non_resident ? form.non_resident.checked : false,
      _token: form._token.value,
    };

    const res = await fetch("{{ url('/stamp-duty/calc') }}", {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token },
      body: JSON.stringify(payload),
    });

    const json = await res.json();
    render(json);
  });

  function render(data){
    // hide placeholder, show sections
    el('placeholder').classList.add('hidden');
    el('summary').classList.remove('hidden');
    el('bands_table').classList.remove('hidden');

    el('jurisdiction').textContent = data.jurisdiction || '';
    el('total_tax').textContent = fmtGBP(data.total_tax || 0);
    el('base_tax').textContent = fmtGBP(data.base_tax || 0);

    // badges
    const badges = [];
    const inputs = data.inputs || {};
    if (inputs.buyerType === 'first_time') badges.push('First‑time buyer');
    if (inputs.isAdditional) badges.push('Additional property');
    if (inputs.isNonResident) badges.push('Non‑resident');
    const badgesWrap = el('badges');
    badgesWrap.innerHTML = badges.map(b => `<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs">${b}</span>`).join('');
    badgesWrap.classList.toggle('hidden', badges.length === 0);

    // bands table
    const body = el('bands_body');
    body.innerHTML = (data.base_breakdown || []).map(row => `
      <tr class="hover:bg-gray-50">
        <td class="px-3 py-2 border">${fmtGBP(row.band_from)}</td>
        <td class="px-3 py-2 border">${(() => { const cap = Number(row.band_to); return (row.band_to === null || !isFinite(cap) || cap > 1e12) ? '∞' : fmtGBP(cap); })()}</td>
        <td class="px-3 py-2 border">${row.rate_pct}%</td>
        <td class="px-3 py-2 border">${fmtGBP(row.amount)}</td>
        <td class="px-3 py-2 border">${fmtGBP(row.tax)}</td>
      </tr>
    `).join('');

    // surcharges
    const sWrap = el('surcharges_wrap');
    const sBody = el('surcharges_body');
    const sur = data.surcharges || [];
    if (sur.length) {
      sBody.innerHTML = sur.map(s => `
        <tr class="hover:bg-gray-50">
          <td class="px-3 py-2 border">${s.label}</td>
          <td class="px-3 py-2 border">${s.rate_pct}%</td>
          <td class="px-3 py-2 border">${fmtGBP(s.amount)}</td>
          <td class="px-3 py-2 border">${fmtGBP(s.tax)}</td>
        </tr>
      `).join('');
      sWrap.classList.remove('hidden');
    } else {
      sBody.innerHTML = '';
      sWrap.classList.add('hidden');
    }

    // raw json
  }

  // Format price input with commas while typing
  const priceInput = el('price');
  if (priceInput) {
    const format = () => {
      const raw = priceInput.value.replace(/,/g, '');
      if (raw === '') { priceInput.value = ''; return; }
      // Keep only digits
      const digits = raw.replace(/\D/g, '');
      if (digits.length === 0) { priceInput.value = ''; return; }
      priceInput.value = Number(digits).toLocaleString('en-GB');
    };
    priceInput.addEventListener('input', format);
    priceInput.addEventListener('blur', format);
  }
})();
</script>
@endsection