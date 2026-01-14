<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\SitemapGenerator;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap.xml';

    public function handle(): int
    {
        $this->info('Generating sitemap...');
        $sitemap = SitemapGenerator::create(config('app.url'))
            // Exclude admin or noisy routes if you want:
            ->shouldCrawl(function ($url) {
                foreach (['/admin', '/login'] as $exclude) {
                    if (str_contains($url, $exclude)) {
                        return false;
                    }
                }
                return true;
            })
            ->getSitemap();

        $areaFile = public_path('data/property_districts.json');
        if (File::exists($areaFile)) {
            $areas = json_decode(File::get($areaFile), true);
            if (is_array($areas)) {
                foreach ($areas as $area) {
                    $path = $area['path'] ?? null;
                    if ($path) {
                        $sitemap->add(Url::create($path));
                    }
                }
            }
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Done: public/sitemap.xml');
        return self::SUCCESS;
    }
}
