@extends('layouts.app')

@section('title', 'EPC Report')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 md:py-12">

    {{-- Hero Panel --}}
    <section class="relative overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center p-8 gap-6">
            <div class="flex-1">
                <h1 class="text-3xl font-semibold text-zinc-900 mb-2">Energy Performance Certificate</h1>
                <p class="text-zinc-600 leading-relaxed">
                    @if(!empty($record['address_display']))
                     {{ rtrim($record['address_display'], ', ') }}@if(!empty($record['postcode'])), {{ strtoupper($record['postcode']) }}@endif
                    @endif
                </p>
            </div>
            <div class="flex-shrink-0">
                <img src="{{ asset('assets/images/site/epc.svg') }}" alt="EPC Report" class="w-64 h-auto md:w-72">
            </div>
        </div>
    </section>

    @php
        $backUrl = $backUrl
            ?? ($nation === 'scotland'
                ? route('epc.search_scotland')
                : route('epc.search'));
    @endphp

    {{-- EPC Rating Panel --}}
    @php
        // Helper: first match from an array of possible keys (case/variant tolerant)
        $pick = function(array $keys) use ($record) {
            foreach ($keys as $k) {
                if (array_key_exists($k, $record) && $record[$k] !== null && $record[$k] !== '') {
                    return $record[$k];
                }
            }
            return null;
        };

        // Numeric (0–100) scores if available
        $curScore = $pick(['current_energy_efficiency','CURRENT_ENERGY_EFFICIENCY','energy_rating_current','ENERGY_RATING_CURRENT']);
        $potScore = $pick(['potential_energy_efficiency','POTENTIAL_ENERGY_EFFICIENCY','energy_rating_potential','ENERGY_RATING_POTENTIAL']);

        // Letter grades if available
        $curLetter = $pick(['current_energy_rating','CURRENT_ENERGY_RATING']);
        $potLetter = $pick(['potential_energy_rating','POTENTIAL_ENERGY_RATING']);

        // Ensure numbers are numeric
        $curScore = is_numeric($curScore) ? (int)$curScore : null;
        $potScore = is_numeric($potScore) ? (int)$potScore : null;

        // Map a score -> EPC band letter
        $bandFor = function($score) {
            if ($score === null) return null;
            if ($score >= 92) return 'A';
            if ($score >= 81) return 'B';
            if ($score >= 69) return 'C';
            if ($score >= 55) return 'D';
            if ($score >= 39) return 'E';
            if ($score >= 21) return 'F';
            if ($score >= 1)  return 'G';
            return null;
        };

        if (!$curLetter) $curLetter = $bandFor($curScore);
        if (!$potLetter) $potLetter = $bandFor($potScore);

        // Visual config
        $bands = [
            ['A', 92, 100, '#22c55e'],
            ['B', 81, 91,  '#84cc16'],
            ['C', 69, 80,  '#eab308'],
            ['D', 55, 68,  '#f59e0b'],
            ['E', 39, 54,  '#f97316'],
            ['F', 21, 38,  '#ef4444'],
            ['G', 1,  20,  '#dc2626'],
        ];

        $w = 960;  // svg width (extra room for Current/Potential columns)
        $rowH = 52;                 // taller rows for readability
        $topPad = 40;               // more headroom for header labels
        $h = ($rowH * 7) + $topPad; // svg height = rows + header row
        $padL = 140; // left label gutter
        $barW = intval(($w - $padL - 220) * 0.5); // half-width bars

        // Convert a score 1–100 into an x-position along the bar
        $xFor = function($score) use ($barW, $padL) {
            if ($score === null) return null;
            $s = max(1, min(100, (int)$score));
            return $padL + ( ($s - 1) / 99 ) * $barW;
        };

        $xCur = $xFor($curScore);
        $xPot = $xFor($potScore);

        // Index of band row (0=A ... 6=G) for right-column markers
        $indexFor = function($letter) {
            if ($letter === null) return null;
            $map = ['A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6];
            $u = strtoupper((string)$letter);
            return $map[$u] ?? null;
        };
        $curIndex = $indexFor($curLetter);
        $potIndex = $indexFor($potLetter);

        // Map band letter -> bar color
        $bandColor = [];
        foreach ($bands as $b) {
            $bandColor[$b[0]] = $b[3];
        }
        $curColor = $curLetter && isset($bandColor[$curLetter]) ? $bandColor[$curLetter] : '#16a34a';
        $potColor = $potLetter && isset($bandColor[$potLetter]) ? $bandColor[$potLetter] : '#22c55e';
    @endphp

    <div class="grid gap-6 md:grid-cols-2">
    <div class="mb-8 rounded-lg border border-zinc-200 bg-white shadow">
        <div class="px-6 py-4 border-b border-zinc-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900">Energy rating</h2>
            <div class="text-sm text-zinc-500">
                @if($curScore) Current: <span class="font-medium text-zinc-700">{{ $curScore }}{{ $curLetter ? ' '.$curLetter : '' }}</span>@endif
                @if($curScore && $potScore) <span class="mx-2">|</span> @endif
                @if($potScore) Potential: <span class="font-medium text-zinc-700">{{ $potScore }}{{ $potLetter ? ' '.$potLetter : '' }}</span>@endif
            </div>
        </div>
        <div class="px-4 py-4 overflow-x-auto">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {{ $w }} {{ $h }}" width="100%" role="img" aria-label="EPC rating bands" preserveAspectRatio="xMidYMid meet">
                @php
                    $tipW = 18;
                    $xBarStart = $padL;
                    $xBarRectW = $barW - $tipW;
                    $xBarTipStart = $xBarStart + $xBarRectW;
                    $rightPad = 16; // minimal right margin
                    // place columns just to the right of the longest bar
                    $xCurCol = $padL + $barW + 100;   // shift further right
                    $xPotCol = $padL + $barW + 220;   // add more spacing between Current and Potential
                @endphp
                @php
                    // Per-band length factors (A shortest → G longest)
                    $lengthFactor = [
                        'A' => 0.40,
                        'B' => 0.50,
                        'C' => 0.60,
                        'D' => 0.70,
                        'E' => 0.80,
                        'F' => 0.90,
                        'G' => 1.00,
                    ];
                @endphp

                {{-- Header row labels above the bands --}}
                <text x="{{ $xCurCol + 24 }}" y="26" font-size="22" fill="#6b7280">Current</text>
                <text x="{{ $xPotCol + 24 }}" y="26" font-size="22" fill="#6b7280">Potential</text>

                @for($i=0; $i<count($bands); $i++)
                    @php
                        [$letter, $min, $max, $fill] = $bands[$i];
                    @endphp
                    @php
                        $y = $topPad + $i * $rowH;
                        $cy = $y + $rowH/2;
                    @endphp
                    {{-- Band label and score range on the left --}}
                    <text x="16" y="{{ $cy + 8 }}" font-size="24" fill="#111827" font-weight="600">{{ $letter }}</text>
                    <text x="56" y="{{ $cy + 8 }}" font-size="20" fill="#4b5563">{{ $min }}–{{ $max }}</text>

                    @php
                        $lf = $lengthFactor[$letter] ?? 1.0;
                        $rowBarW = max(40, intval($barW * $lf));
                        $rowRectW = max(20, $rowBarW - $tipW);
                        $rowTipStart = $xBarStart + $rowRectW;
                    @endphp
                    {{-- Arrow bar (rounded rect + triangular tip) --}}
                    <rect x="{{ $xBarStart }}" y="{{ $y+4 }}" width="{{ $rowRectW }}" height="{{ $rowH-8 }}" rx="6" ry="6" fill="{{ $fill }}" />
                    <polygon points="{{ $rowTipStart }},{{ $y+4 }} {{ $rowTipStart+$tipW }},{{ $cy }} {{ $rowTipStart }},{{ $y+$rowH-4 }}" fill="{{ $fill }}" />

                    {{-- Light row separator line --}}
                    <line x1="{{ $padL }}" y1="{{ $y + $rowH }}" x2="{{ $w - $rightPad }}" y2="{{ $y + $rowH }}" stroke="#e5e7eb" stroke-width="1" />
                @endfor

                {{-- Current marker in right column --}}
                @if(!is_null($curIndex))
                    @php
                        $yMarker = $topPad + $curIndex * $rowH + $rowH/2;
                    @endphp
                    <g transform="translate({{ $xCurCol }}, {{ $yMarker }})">
                        <polygon points="0,0 14,-8 14,8" fill="{{ $curColor }}" />
                        <rect x="14" y="-14" width="72" height="28" rx="6" fill="{{ $curColor }}" />
                        <text x="56" y="9" text-anchor="middle" font-size="20" fill="#ffffff">{{ $curScore }} {{ $curLetter }}</text>
                        <title>Current: {{ $curScore }} {{ $curLetter }}</title>
                    </g>
                @endif

                {{-- Potential marker in right column --}}
                @if(!is_null($potIndex))
                    @php
                        $yMarker = $topPad + $potIndex * $rowH + $rowH/2;
                    @endphp
                    <g transform="translate({{ $xPotCol }}, {{ $yMarker }})">
                        <polygon points="0,0 14,-8 14,8" fill="{{ $potColor }}" />
                        <rect x="14" y="-14" width="72" height="28" rx="6" fill="{{ $potColor }}" />
                        <text x="56" y="9" text-anchor="middle" font-size="20" fill="#ffffff">{{ $potScore }} {{ $potLetter }}</text>
                        <title>Potential: {{ $potScore }} {{ $potLetter }}</title>
                    </g>
                @endif
            </svg>
        </div>
    </div>

    {{-- Environmental Impact Panel (CO₂) --}}
    @php
        // Try multiple key variants across datasets
        $envCur = $pick([
            'environment_impact_current','ENVIRONMENT_IMPACT_CURRENT',
            'co2_emissions_current_per_floor_area','CO2_EMISS_CURR_PER_FLOOR_AREA',
            'environmental_impact_current','ENVIRONMENTAL_IMPACT_CURRENT'
        ]);
        $envPot = $pick([
            'environment_impact_potential','ENVIRONMENT_IMPACT_POTENTIAL',
            'co2_emissions_potential_per_floor_area','CO2_EMISS_POT_PER_FLOOR_AREA',
            'environmental_impact_potential','ENVIRONMENTAL_IMPACT_POTENTIAL'
        ]);

        $envCur = is_numeric($envCur) ? (int)$envCur : null;
        $envPot = is_numeric($envPot) ? (int)$envPot : null;

        $envCurLetter = $envCur ? $bandFor($envCur) : $pick(['environment_impact_rating_current','ENVIRONMENT_IMPACT_RATING_CURRENT','environmental_impact_rating_current','ENVIRONMENTAL_IMPACT_RATING_CURRENT']);
        $envPotLetter = $envPot ? $bandFor($envPot) : $pick(['environment_impact_rating_potential','ENVIRONMENT_IMPACT_RATING_POTENTIAL','environmental_impact_rating_potential','ENVIRONMENTAL_IMPACT_RATING_POTENTIAL']);

        // Blue/grey palette (A brightest blue → G darkest grey)
        $envBands = [
            ['A', 92, 100, '#60a5fa'],
            ['B', 81, 91,  '#3b82f6'],
            ['C', 69, 80,  '#2563eb'],
            ['D', 55, 68,  '#1d4ed8'],
            ['E', 39, 54,  '#9ca3af'],
            ['F', 21, 38,  '#6b7280'],
            ['G', 1,  20,  '#4b5563'],
        ];

        $w2 = $w;            // reuse layout dims
        $h2 = $h;
        $rowH2 = $rowH;
        $topPad2 = $topPad;
        $padL2 = $padL;
        $barW2 = $barW;

        $tipW2 = 18;
        $xBarStart2 = $padL2; $xBarRectW2 = $barW2 - $tipW2; $xBarTipStart2 = $xBarStart2 + $xBarRectW2;
        $rightPad2 = 16; // minimal right margin
        // place columns just to the right of the longest bar
        $xCurCol2 = $padL2 + $barW2 + 100;   // shift further right
        $xPotCol2 = $padL2 + $barW2 + 220;   // add more spacing between Current and Potential

        $indexFor2 = $indexFor; // same mapping A→index
        $envCurIndex = $envCurLetter ? $indexFor2($envCurLetter) : null;
        $envPotIndex = $envPotLetter ? $indexFor2($envPotLetter) : null;

        // Same length factors (A shortest → G longest)
        $lengthFactor2 = [
            'A' => 0.40,
            'B' => 0.50,
            'C' => 0.60,
            'D' => 0.70,
            'E' => 0.80,
            'F' => 0.90,
            'G' => 1.00,
        ];

        $envBandColor = [];
        foreach ($envBands as $b) { $envBandColor[$b[0]] = $b[3]; }
        $envCurColor = $envCurLetter && isset($envBandColor[$envCurLetter]) ? $envBandColor[$envCurLetter] : '#60a5fa';
        $envPotColor = $envPotLetter && isset($envBandColor[$envPotLetter]) ? $envBandColor[$envPotLetter] : '#60a5fa';
    @endphp

    <div class="mb-8 rounded-lg border border-zinc-200 bg-white shadow">
        <div class="px-6 py-4 border-b border-zinc-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900">Environmental impact (CO₂)</h2>
            <div class="text-sm text-zinc-500">
                @if(!is_null($envCur))
                    Current: <span class="font-medium text-zinc-700">{{ $envCur }}</span>
                @endif
                @if(!is_null($envCur) && !is_null($envPot)) <span class="mx-2">|</span> @endif
                @if(!is_null($envPot))
                    Potential: <span class="font-medium text-zinc-700">{{ $envPot }}</span>
                @endif
            </div>
        </div>
        <div class="px-4 py-4 overflow-x-auto">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {{ $w2 }} {{ $h2 }}" width="100%" role="img" aria-label="Environmental impact bands" preserveAspectRatio="xMidYMid meet">
                {{-- Header labels above bands --}}
                <text x="{{ $xCurCol2 + 24 }}" y="26" font-size="22" fill="#6b7280">Current</text>
                <text x="{{ $xPotCol2 + 24 }}" y="26" font-size="22" fill="#6b7280">Potential</text>

                @for($i=0; $i<count($envBands); $i++)
                    @php
                        [$letter, $min, $max, $fill] = $envBands[$i];
                    @endphp
                    @php
                        $y = $topPad2 + $i * $rowH2;
                        $cy = $y + $rowH2/2;
                    @endphp

                    {{-- Labels --}}
                    <text x="16" y="{{ $cy + 8 }}" font-size="24" fill="#111827" font-weight="600">{{ $letter }}</text>
                    <text x="56" y="{{ $cy + 8 }}" font-size="20" fill="#4b5563">{{ $min }}–{{ $max }}</text>

                    @php
                        $lf2 = $lengthFactor2[$letter] ?? 1.0;
                        $rowBarW2 = max(40, intval($barW2 * $lf2));
                        $rowRectW2 = max(20, $rowBarW2 - $tipW2);
                        $rowTipStart2 = $xBarStart2 + $rowRectW2;
                    @endphp
                    {{-- Arrow bar --}}
                    <rect x="{{ $xBarStart2 }}" y="{{ $y+4 }}" width="{{ $rowRectW2 }}" height="{{ $rowH2-8 }}" rx="6" ry="6" fill="{{ $fill }}" />
                    <polygon points="{{ $rowTipStart2 }},{{ $y+4 }} {{ $rowTipStart2+$tipW2 }},{{ $cy }} {{ $rowTipStart2 }},{{ $y+$rowH2-4 }}" fill="{{ $fill }}" />

                    <line x1="{{ $padL2 }}" y1="{{ $y + $rowH2 }}" x2="{{ $w2 - $rightPad2 }}" y2="{{ $y + $rowH2 }}" stroke="#e5e7eb" stroke-width="1" />
                @endfor

                {{-- Current marker --}}
                @if(!is_null($envCurIndex))
                    @php
                        $yMarker = $topPad2 + $envCurIndex * $rowH2 + $rowH2/2;
                    @endphp
                    <g transform="translate({{ $xCurCol2 }}, {{ $yMarker }})">
                        <polygon points="0,0 14,-8 14,8" fill="{{ $envCurColor }}" />
                        <rect x="14" y="-14" width="72" height="28" rx="6" fill="{{ $envCurColor }}" />
                        <text x="56" y="9" text-anchor="middle" font-size="20" fill="#ffffff">{{ $envCur }}</text>
                        <title>Current: {{ $envCur }}</title>
                    </g>
                @endif

                {{-- Potential marker --}}
                @if(!is_null($envPotIndex))
                    @php
                        $yMarker = $topPad2 + $envPotIndex * $rowH2 + $rowH2/2;
                    @endphp
                    <g transform="translate({{ $xPotCol2 }}, {{ $yMarker }})">
                        <polygon points="0,0 14,-8 14,8" fill="{{ $envPotColor }}" />
                        <rect x="14" y="-14" width="72" height="28" rx="6" fill="{{ $envPotColor }}" />
                        <text x="56" y="9" text-anchor="middle" font-size="20" fill="#ffffff">{{ $envPot }}</text>
                        <title>Potential: {{ $envPot }}</title>
                    </g>
                @endif
            </svg>
        </div>
    </div>
    </div>

    <div class="overflow-x-auto bg-white shadow rounded-lg border border-zinc-200">
        <table class="min-w-full text-sm text-left text-zinc-700">
            <thead class="bg-zinc-100 text-zinc-800 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 border-b">Field</th>
                    <th class="px-4 py-2 border-b">Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($record as $key => $value)
                    @if(!is_array($value))
                        <tr class="border-b hover:bg-zinc-50">
                            <td class="px-4 py-2 font-medium text-zinc-800">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                            <td class="px-4 py-2 text-zinc-600">{{ $value ?? '—' }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <a href="{{ $backUrl }}" class="inline-block px-4 py-2 bg-lime-600 text-white rounded hover:bg-lime-700">
            ← Back to results
        </a>
    </div>

</div>
@endsection