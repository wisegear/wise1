<?php

namespace App\Console\Commands;

use App\Http\Controllers\PropertyAreaController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PropertyAreaWarm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Optional:
     *   --type=county     (only warm a specific type)
     *   --limit=100       (stop after N areas, handy for testing)
     */
    protected $signature = 'area:warm {--type=} {--limit=}';

    /**
     * The console command description.
     */
    protected $description = 'Warm caches for all property area pages from the JSON index';

    public function handle(): int
    {
        $jsonPath = public_path('data/property_districts.json');

        if (! file_exists($jsonPath)) {
            $this->error("JSON index not found at {$jsonPath}");
            return 1;
        }

        $areas = json_decode(file_get_contents($jsonPath), true) ?? [];

        $allowedTypes = ['locality', 'town', 'district', 'county'];
        $columnMap = [
            'locality' => 'Locality',
            'town'     => 'TownCity',
            'district' => 'District',
            'county'   => 'County',
        ];

        $filterType = $this->option('type') ? strtolower($this->option('type')) : null;
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $ttl = now()->addDays(45);
        $count = 0;

        /** @var \App\Http\Controllers\PropertyAreaController $controller */
        $controller = app(PropertyAreaController::class);

        foreach ($areas as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = strtolower($item['type'] ?? '');
            if (! in_array($type, $allowedTypes, true)) {
                continue;
            }

            if ($filterType && $type !== $filterType) {
                continue;
            }

            $areaName = $item['name'] ?? $item['label'] ?? null;
            if (! $areaName) {
                continue;
            }

            $column = $columnMap[$type] ?? null;
            if (! $column) {
                continue;
            }

            $slug = Str::slug($areaName);
            $cacheKey = 'area:v1:' . $type . ':' . $slug;

            // Build payload using the same logic as the controller
            $payload = $controller->buildAreaPayload($column, $areaName);

            Cache::put($cacheKey, $payload, $ttl);

            $count++;
            $this->info("Warmed {$type} | {$areaName} ({$cacheKey})");

            if ($limit && $count >= $limit) {
                break;
            }
        }

        $this->info("Finished. Warmed {$count} areas.");
        return 0;
    }
}
