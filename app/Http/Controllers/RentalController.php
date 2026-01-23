<?php

namespace App\Http\Controllers;

use App\Models\RentalCost;

class RentalController extends Controller
{
    public function index()
    {
        $areas = [
            'United Kingdom',
            'England',
            'Scotland',
            'Wales',
            'Northern Ireland',
        ];

        $latestPeriod = $this->latestTimePeriod();
        $seriesByArea = $this->buildSeriesByArea($areas);

        return view('rental.index', [
            'seriesByArea' => $seriesByArea,
            'latestPeriod' => $latestPeriod,
        ]);
    }

    public function england()
    {
        return $this->nationView('England', 'rental.england');
    }

    public function scotland()
    {
        return $this->nationView('Scotland', 'rental.scotland');
    }

    public function wales()
    {
        return $this->nationView('Wales', 'rental.wales');
    }

    public function northernIreland()
    {
        return $this->nationView('Northern Ireland', 'rental.northern-ireland');
    }

    private function parseTimePeriod(?string $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (is_numeric($trimmed)) {
            return $this->excelSerialToDateTime((float) $trimmed);
        }

        $formats = ['M-Y', 'Y-m', 'Y-m-d'];
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $trimmed, new \DateTimeZone('UTC'));
            if ($date instanceof \DateTimeImmutable) {
                return $date;
            }
        }

        $parsed = date_create($trimmed, new \DateTimeZone('UTC'));
        return $parsed ? \DateTimeImmutable::createFromMutable($parsed) : null;
    }

    private function excelSerialToDateTime(float $serial): ?\DateTimeImmutable
    {
        if ($serial < 1) {
            return null;
        }

        $days = (int) floor($serial);
        $seconds = (int) round(($serial - $days) * 86400);

        $base = new \DateTimeImmutable('1899-12-30', new \DateTimeZone('UTC'));
        $date = $base->modify('+' . $days . ' days');
        if ($seconds > 0) {
            $date = $date->modify('+' . $seconds . ' seconds');
        }

        return $date;
    }

    private function latestTimePeriod(?string $areaName = null): ?string
    {
        $query = RentalCost::query()
            ->select('time_period')
            ->distinct();

        if ($areaName) {
            $query->where('area_name', $areaName);
        }

        $rows = $query->get();

        $latest = null;
        $latestTs = null;

        foreach ($rows as $row) {
            $date = $this->parseTimePeriod($row->time_period);
            if (!$date) {
                continue;
            }

            $ts = $date->getTimestamp();
            if ($latestTs === null || $ts > $latestTs) {
                $latestTs = $ts;
                $latest = $date;
            }
        }

        return $latest ? $latest->format('M Y') : null;
    }

    private function buildSeriesByArea(array $areas): array
    {
        $seriesByArea = [];

        foreach ($areas as $name) {
            $rows = RentalCost::query()
                ->select(['time_period', 'rental_price', 'monthly_change'])
                ->where('area_name', $name)
                ->orderByRaw("COALESCE(STR_TO_DATE(time_period, '%b-%Y'), STR_TO_DATE(time_period, '%Y-%m'), STR_TO_DATE(time_period, '%Y-%m-%d'))")
                ->get();

            $series = $this->buildQuarterlySeries($rows, 'rental_price', 'monthly_change');
            $seriesByArea[] = array_merge(['name' => $name], $series);
        }

        return $seriesByArea;
    }

    private function nationView(string $nationName, string $view)
    {
        $seriesByArea = $this->buildSeriesByArea([$nationName]);
        $latestPeriod = $this->latestTimePeriod($nationName);
        $typeSeries = $this->buildTypeSeriesForArea($nationName);

        return view($view, [
            'seriesByArea' => $seriesByArea,
            'latestPeriod' => $latestPeriod,
            'nationName' => $nationName,
            'typeSeries' => $typeSeries,
        ]);
    }

    private function buildQuarterlySeries($rows, string $priceField, string $changeField): array
    {
        $sorted = $rows->map(function ($row) use ($priceField, $changeField) {
            $date = $this->parseTimePeriod($row->time_period);
            $quarter = $date ? 'Q' . (int) ceil(((int) $date->format('n')) / 3) : null;
            return [
                'label' => $date && $quarter ? $date->format('Y') . '-' . $quarter : $row->time_period,
                'ts' => $date ? $date->getTimestamp() : null,
                'rental_price' => $row->{$priceField} ?? null,
                'monthly_change' => $row->{$changeField} ?? null,
            ];
        })
            ->filter(fn($row) => $row['ts'] !== null)
            ->sortBy('ts')
            ->values();

        $quarterly = $sorted->groupBy('label')->map(function ($items, $quarter) {
            $priceSum = 0.0;
            $priceCount = 0;
            $changeSum = 0.0;
            $changeCount = 0;

            foreach ($items as $item) {
                if ($item['rental_price'] !== null) {
                    $priceSum += (float) $item['rental_price'];
                    $priceCount++;
                }
                if ($item['monthly_change'] !== null) {
                    $changeSum += (float) $item['monthly_change'];
                    $changeCount++;
                }
            }

            return [
                'period' => $quarter,
                'rental_price' => $priceCount ? $priceSum / $priceCount : null,
                'monthly_change' => $changeCount ? $changeSum / $changeCount : null,
            ];
        })->sortKeys()->values();

        return [
            'labels' => $quarterly->pluck('period')->values()->all(),
            'prices' => $quarterly->pluck('rental_price')->map(fn($v) => is_null($v) ? null : (float) $v)->values()->all(),
            'changes' => $quarterly->pluck('monthly_change')->map(fn($v) => is_null($v) ? null : (float) $v)->values()->all(),
        ];
    }

    private function buildTypeSeriesForArea(string $areaName): array
    {
        $types = [
            ['key' => 'one_bed', 'label' => 'One bed', 'price' => 'rental_price_one_bed', 'change' => 'monthly_change_one_bed'],
            ['key' => 'two_bed', 'label' => 'Two bed', 'price' => 'rental_price_two_bed', 'change' => 'monthly_change_two_bed'],
            ['key' => 'three_bed', 'label' => 'Three bed', 'price' => 'rental_price_three_bed', 'change' => 'monthly_change_three_bed'],
            ['key' => 'four_plus_bed', 'label' => 'Four or more bed', 'price' => 'rental_price_four_or_more_bed', 'change' => 'monthly_change_four_or_more_bed'],
            ['key' => 'detached', 'label' => 'Detached', 'price' => 'rental_price_detached', 'change' => 'monthly_change_detached'],
            ['key' => 'semidetached', 'label' => 'Semi-detached', 'price' => 'rental_price_semidetached', 'change' => 'monthly_change_semidetached'],
            ['key' => 'terraced', 'label' => 'Terraced', 'price' => 'rental_price_terraced', 'change' => 'monthly_change_terraced'],
            ['key' => 'flat_maisonette', 'label' => 'Flat/maisonette', 'price' => 'rental_price_flat_maisonette', 'change' => 'monthly_change_flat_maisonette'],
        ];

        $columns = ['time_period'];
        foreach ($types as $type) {
            $columns[] = $type['price'];
            $columns[] = $type['change'];
        }

        $rows = RentalCost::query()
            ->select($columns)
            ->where('area_name', $areaName)
            ->orderByRaw("COALESCE(STR_TO_DATE(time_period, '%b-%Y'), STR_TO_DATE(time_period, '%Y-%m'), STR_TO_DATE(time_period, '%Y-%m-%d'))")
            ->get();

        $series = [];
        foreach ($types as $type) {
            $data = $this->buildQuarterlySeries($rows, $type['price'], $type['change']);
            $series[] = array_merge($type, $data);
        }

        return $series;
    }
}
