<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExportRepossessionsLocalAuthorities extends Command
{
    /**
     * php artisan repossessions:export-local-authorities
     */
    protected $signature = 'repossessions:export-local-authorities';

    protected $description = 'Export unique local authorities from repossession data to JSON.';

    public function handle(): int
    {
        $this->info('Exporting repossession local authorities…');

        $rows = DB::table('repo_la_quarterlies')
            ->selectRaw('DISTINCT TRIM(local_authority) AS local_authority')
            ->whereNotNull('local_authority')
            ->whereRaw("TRIM(local_authority) <> ''")
            ->orderBy('local_authority')
            ->pluck('local_authority');

        if ($rows->isEmpty()) {
            $this->warn('No local authorities found.');
            return self::SUCCESS;
        }

        $authorities = $rows->map(function ($name) {
            return [
                'name'  => $name,
                'label' => $name,
                'slug'  => Str::slug($name),
                'path'  => '/repossessions/local-authority/' . Str::slug($name),
            ];
        })->values();

        $dir = public_path('data');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir . '/repo_local_authorities.json';

        file_put_contents(
            $file,
            json_encode($authorities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->info('Exported ' . $authorities->count() . ' local authorities to: ' . $file);

        return self::SUCCESS;
    }
}
