<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HomepageStatsWarm extends Command
{
    protected $signature = 'home:stats-warm';
    protected $description = 'Warm cached stats displayed on the homepage';

    public function handle(): int
    {
        $this->info('Warming homepage stats cache...');

        $ttl = now()->addDays(30);

        // Total property records in land_registry
        $this->line('→ Counting property records...');
        $propertyCount = DB::table('land_registry')->count();
        $this->line("   " . number_format($propertyCount) . " records");

        // UK average property price from HPI (latest)
        $this->line('→ Fetching UK average price from HPI...');
        $latestHpi = DB::table('hpi_monthly')
            ->where('AreaCode', 'K02000001')
            ->orderBy('Date', 'desc')
            ->first();
        $ukAvgPrice = $latestHpi->AveragePrice ?? 0;
        $this->line("   £" . number_format($ukAvgPrice));

        // UK average rent (latest quarter)
        $this->line('→ Fetching UK average rent...');
        $ukAvgRent = 0;
        $rentRows = DB::table('rental_costs')
            ->select(['time_period', 'rental_price'])
            ->where('area_name', 'United Kingdom')
            ->get();

        $latestQuarterKey = null;
        $latestQuarterTs = null;

        foreach ($rentRows as $row) {
            $date = $this->parseTimePeriod($row->time_period);
            if (!$date) {
                continue;
            }

            $quarter = (int) ceil(((int) $date->format('n')) / 3);
            $key = $date->format('Y') . '-Q' . $quarter;
            $ts = $date->getTimestamp();

            if ($latestQuarterTs === null || $ts > $latestQuarterTs) {
                $latestQuarterTs = $ts;
                $latestQuarterKey = $key;
            }
        }

        if ($latestQuarterKey) {
            $sum = 0.0;
            $count = 0;

            foreach ($rentRows as $row) {
                $date = $this->parseTimePeriod($row->time_period);
                if (!$date) {
                    continue;
                }

                $quarter = (int) ceil(((int) $date->format('n')) / 3);
                $key = $date->format('Y') . '-Q' . $quarter;
                if ($key !== $latestQuarterKey) {
                    continue;
                }

                if ($row->rental_price !== null) {
                    $sum += (float) $row->rental_price;
                    $count++;
                }
            }

            $ukAvgRent = $count ? ($sum / $count) : 0;
        }
        $this->line("   £" . number_format($ukAvgRent));

        // Latest bank rate
        $this->line('→ Fetching latest bank rate...');
        $latestRate = DB::table('interest_rates')
            ->orderBy('effective_date', 'desc')
            ->first();
        $bankRate = $latestRate->rate ?? 0;
        $this->line("   {$bankRate}%");

        // Total EPC records
        $this->line('→ Counting EPC records...');
        $epcCount = DB::table('epc_certificates')->count();
        $this->line("   " . number_format($epcCount) . " records");

        // Store in cache
        $stats = [
            'property_records' => $propertyCount,
            'uk_avg_price' => round($ukAvgPrice),
            'uk_avg_rent' => round($ukAvgRent),
            'bank_rate' => $bankRate,
            'epc_count' => $epcCount,
        ];

        Cache::put('homepage_stats', $stats, $ttl);
        $this->line('→ homepage_stats cached for 30 days');

        // Record last warm time
        Cache::put('homepage_stats:last_warm', now());

        $this->info('Homepage stats cache warmed successfully.');

        return Command::SUCCESS;
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
}
