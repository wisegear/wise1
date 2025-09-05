<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StampDutyController extends Controller
{
    /**
     * Show the calculator page (you can point this at a Blade view).
     * If you haven’t made the Blade yet, this will still let you POST to /stamp-duty/calc and get JSON back.
     */
    public function index()
    {
        // Return a view if you create one, else a tiny helper message
        return view('stamp-duty.index');
        return response()->json([
            'message' => 'POST to /stamp-duty/calc with {price, region, buyer_type, additional_property, non_resident} to get calculations.'
        ]);
    }

    /**
     * Calculate SDLT (England & NI), LBTT (Scotland) or LTT (Wales) based on inputs.
     * Request expects:
     *  - price: numeric
     *  *  - region: one of: eng-ni | scotland | wales
     *  - buyer_type: main | first_time
     *  - additional_property: bool (true if buying an additional property / second home)
     *  - non_resident: bool (applies only to SDLT; ignored elsewhere)
     */
    public function calculate(Request $request)
    {
        $data = $request->validate([
            'price' => ['required','numeric','min:0'],
            'region' => ['required', Rule::in(['eng-ni','scotland','wales'])],
            'buyer_type' => ['required', Rule::in(['main','first_time'])],
            'additional_property' => ['required','boolean'],
            'non_resident' => ['required','boolean'],
        ]);

        $price = (float) $data['price'];
        $region = $data['region'];
        $buyerType = $data['buyer_type'];
        $isAdditional = (bool) $data['additional_property'];
        $isNonResident = (bool) $data['non_resident'];

        // Guardrails: first‑time buyers can’t also be "+ additional property" for relief purposes
        if ($buyerType === 'first_time' && $isAdditional) {
            $buyerType = 'main'; // ignore FTB relief if they say it’s an additional property
        }

        switch ($region) {
            case 'eng-ni':
                $result = $this->calcSdlt($price, $buyerType, $isAdditional, $isNonResident);
                break;
            case 'scotland':
                $result = $this->calcLbtt($price, $buyerType, $isAdditional);
                break;
            case 'wales':
                $result = $this->calcLtt($price, $buyerType, $isAdditional);
                break;
            default:
                abort(422, 'Unsupported region');
        }

        return response()->json($result);
    }

    /**
     * Progressive calculator helper.
     * $bands is an array of [thresholdUpper, ratePercent]. Use PHP_FLOAT_MAX for the last upper bound.
     * Returns ['tax' => float, 'bands' => array]
     */
    private function progressive(float $price, array $bands): array
    {
        $remaining = $price;
        $lastCap = 0.0;
        $tax = 0.0;
        $breakdown = [];

        foreach ($bands as [$cap, $rate]) {
            $portion = max(0.0, min($price, $cap) - $lastCap);
            if ($portion > 0) {
                $portionTax = $portion * ($rate / 100);
                $tax += $portionTax;
                $breakdown[] = [
                    'band_from' => $lastCap,
                    'band_to'   => $cap,
                    'rate_pct'  => $rate,
                    'amount'    => $portion,
                    'tax'       => $portionTax,
                ];
            }
            $lastCap = $cap;
            if ($lastCap >= $price) break;
        }

        return ['tax' => round($tax, 2), 'bands' => $breakdown];
    }

    /**
     * SDLT (England & NI) — rates effective April 2025 per GOV.UK.
     * Main residence bands: 0% to £125k; 2% £125k–£250k; 5% £250k–£925k; 10% £925k–£1.5m; 12% 1.5m+.
     * First‑time buyer relief: 0% to £300k; 5% £300k–£500k; no relief if price > £500k.
     * Additional property surcharge: +5% flat across whole price.
     * Non‑resident surcharge: +2% flat across whole price.
     */
    private function calcSdlt(float $price, string $buyerType, bool $isAdditional, bool $isNonResident): array
    {
        // Choose base bands
        if ($buyerType === 'first_time' && $price <= 500000) {
            $bands = [
                [300000, 0],
                [500000, 5],
                [925000, 5],   // beyond £500k FTB relief ends; the 5% here only applies to the slice £300–500k
                [1500000, 10],
                [PHP_FLOAT_MAX, 12],
            ];
        } else {
            $bands = [
                [125000, 0],
                [250000, 2],
                [925000, 5],
                [1500000, 10],
                [PHP_FLOAT_MAX, 12],
            ];
        }

        $base = $this->progressive($price, $bands);

        // Surcharges
        $surcharges = [];
        $surchargeTotal = 0.0;

        if ($isAdditional) {
            $add = round($price * 0.05, 2); // 5% on total consideration
            $surcharges[] = ['label' => 'Higher rates (additional property)', 'rate_pct' => 5, 'amount' => $price, 'tax' => $add];
            $surchargeTotal += $add;
        }
        if ($isNonResident) {
            $nr = round($price * 0.02, 2); // 2% on total consideration
            $surcharges[] = ['label' => 'Non‑resident surcharge', 'rate_pct' => 2, 'amount' => $price, 'tax' => $nr];
            $surchargeTotal += $nr;
        }

        return [
            'jurisdiction' => 'SDLT (England & Northern Ireland)',
            'inputs' => compact('price','buyerType','isAdditional','isNonResident'),
            'base_tax' => $base['tax'],
            'base_breakdown' => $base['bands'],
            'surcharges' => $surcharges,
            'total_tax' => round($base['tax'] + $surchargeTotal, 2),
        ];
    }

    /**
     * LBTT (Scotland) — bands unchanged; ADS is 8% flat on whole price from 5 Dec 2024.
     * Main bands: 0% to £145k; 2% £145k–£250k; 5% £250k–£325k; 10% £325k–£750k; 12% 750k+.
     * First‑time buyer relief: nil‑rate band increases to £175k (max £600 benefit).
     */
    private function calcLbtt(float $price, string $buyerType, bool $isAdditional): array
    {
        $bands = [
            [145000, 0],
            [250000, 2],
            [325000, 5],
            [750000, 10],
            [PHP_FLOAT_MAX, 12],
        ];

        // Apply first‑time buyer increased nil‑rate band to £175k
        if ($buyerType === 'first_time') {
            $bands = [
                [175000, 0],
                [250000, 2],
                [325000, 5],
                [750000, 10],
                [PHP_FLOAT_MAX, 12],
            ];
        }

        $base = $this->progressive($price, $bands);

        $surcharges = [];
        $surchargeTotal = 0.0;

        if ($isAdditional) {
            $ads = round($price * 0.08, 2); // ADS 8% on consideration
            $surcharges[] = ['label' => 'Additional Dwelling Supplement (ADS)', 'rate_pct' => 8, 'amount' => $price, 'tax' => $ads];
            $surchargeTotal += $ads;
        }

        return [
            'jurisdiction' => 'LBTT (Scotland)',
            'inputs' => compact('price','buyerType','isAdditional'),
            'base_tax' => $base['tax'],
            'base_breakdown' => $base['bands'],
            'surcharges' => $surcharges,
            'total_tax' => round($base['tax'] + $surchargeTotal, 2),
        ];
    }

    /**
     * LTT (Wales)
     * Main residential bands (from 10 Oct 2022): 0% to £225k; 6% £225k–£400k; 7.5% £400k–£750k; 10% £750k–£1.5m; 12% 1.5m+.
     * Higher residential rates (from 11 Dec 2024): 5%, 8.5%, 10%, 12.5%, 15%, 17% tiered bands (starting at £0–£180k).
     * Wales does not have a FTB relief (keep buyer_type for symmetry but it does not change bands).
     */
    private function calcLtt(float $price, string $buyerType, bool $isAdditional): array
    {
        // Base (main residence) bands
        $mainBands = [
            [225000, 0],
            [400000, 6],
            [750000, 7.5],
            [1500000, 10],
            [PHP_FLOAT_MAX, 12],
        ];

        // Higher (additional property) bands as of 11 Dec 2024
        $higherBands = [
            [180000, 5],
            [250000, 8.5],
            [400000, 10],
            [750000, 12.5],
            [1500000, 15],
            [PHP_FLOAT_MAX, 17],
        ];

        $bands = $isAdditional ? $higherBands : $mainBands;
        $base = $this->progressive($price, $bands);

        return [
            'jurisdiction' => 'LTT (Wales)',
            'inputs' => compact('price','buyerType','isAdditional'),
            'base_tax' => $base['tax'],
            'base_breakdown' => $base['bands'],
            'surcharges' => [],
            'total_tax' => $base['tax'],
        ];
    }
}
