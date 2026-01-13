@php
    $totalStress = $totalStress ?? null;

    $isSticky = $isSticky ?? true;

    if (is_null($totalStress)) {
        $stressScore = null;
    } else {
        // Convert to 0–100 scale (max possible is 31: seven 4-point indicators plus arrears (0–3))
        $scaled = max(0, min(100, round(($totalStress / 31) * 100)));
        $stressScore = $scaled;

        // Determine stress level and styling
        if ($stressScore >= 70) {
            $stressLabel = 'High stress';
            $stressClass = 'bg-rose-50 text-rose-800 border-rose-200';
        } elseif ($stressScore >= 40) {
            $stressLabel = 'Elevated risk';
            $stressClass = 'bg-amber-50 text-amber-800 border-amber-200';
        } else {
            $stressLabel = 'Low stress';
            $stressClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
        }

        $gaugeRotation = $stressScore <= 40
            ? -90 + pow(($stressScore / 40), 1.6) * 71.79
            : ($stressScore <= 69
                ? -18.21 + (($stressScore - 40) / 29) * 52.10
                : 33.89 + (($stressScore - 70) / 30) * 56.11);
    }
@endphp

@if(!is_null($stressScore))
    @php
        $panelClasses = 'mb-8 rounded-xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-5 md:p-6 shadow-lg';
        if ($isSticky) {
            $panelClasses .= ' sticky top-0 z-40 backdrop-blur-sm bg-white/95';
        }
    @endphp
    <section class="{{ $panelClasses }}">
        <div class="flex flex-col gap-4 md:grid md:grid-cols-3 md:items-center">
            {{-- Left: Title and description --}}
            <div class="md:col-span-1">
                <h2 class="text-sm font-semibold tracking-wide text-gray-700 uppercase">
                    Overall Property Stress Index
                </h2>
                <p class="mt-1 text-xs text-gray-700 hidden md:block">
                    A single 0–100 score combining all eight indicators. Higher scores mean more stress and risk.
                </p>
            </div>

            {{-- Center: Semi-circular gauge --}}
            <div class="flex flex-col items-center md:col-span-1">
                <div class="relative w-44 h-24">
                    <svg class="w-44 h-24" viewBox="0 0 200 120" aria-hidden="true">
                        <!-- Green zone (0–40) -->
                        <path d="M 20 100 A 80 80 0 0 1 75 24"
                              fill="none"
                              stroke="#d1fae5"
                              stroke-width="12"
                              stroke-linecap="round" />

                        <!-- Amber zone (40–69) -->
                        <path d="M 75 24 A 80 80 0 0 1 145 33"
                              fill="none"
                              stroke="#fef3c7"
                              stroke-width="12"
                              stroke-linecap="round" />

                        <!-- Red zone (70–100) -->
                        <path d="M 145 33 A 80 80 0 0 1 180 100"
                              fill="none"
                              stroke="#fecaca"
                              stroke-width="12"
                              stroke-linecap="round" />

                        <!-- Needle -->
                        <g transform="rotate({{ $gaugeRotation }}, 100, 100)">
                            <line x1="100" y1="100" x2="100" y2="32"
                                  stroke="#1f2937"
                                  stroke-width="3"
                                  stroke-linecap="round" />
                            <circle cx="100" cy="100" r="5" fill="#1f2937" />
                        </g>

                        <!-- Scale labels -->
                        <text x="16" y="116" class="text-[10px] fill-gray-500">0</text>
                        <text x="96" y="18" class="text-[10px] fill-gray-500">50</text>
                        <text x="176" y="116" class="text-[10px] fill-gray-500">100</text>
                    </svg>
                </div>
                <div class="flex items-baseline gap-1 -mt-2">
                    <span class="text-3xl md:text-4xl font-bold text-gray-900">{{ $stressScore }}</span>
                    <span class="text-xs text-gray-500">/ 100</span>
                </div>
                <span class="mt-1 rounded-full border px-3 py-1 text-[11px] font-medium {{ $stressClass }} whitespace-nowrap">
                    {{ $stressLabel }}
                </span>
            </div>

            {{-- Right: Score explanation --}}
            <div class="flex flex-col items-end text-right md:col-span-1">
                <div class="text-sm uppercase tracking-wide text-gray-700 font-semibold mb-2">Score guide</div>
                <p class="text-xs text-gray-600">
                    The score rolls up eight indicators into a 0–100 index. Under 40 is low stress,
                    40–69 signals elevated risk, and 70+ points to high stress. Use it to compare
                    momentum over time rather than a single-month snapshot.
                </p>
                <div class="text-[10px] text-gray-500 mt-2">Raw: {{ $totalStress }}/31</div>
            </div>
        </div>
    </section>
@endif
