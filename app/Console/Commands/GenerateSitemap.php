<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\SitemapGenerator;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap.xml';

    public function handle(): int
    {
        $this->info('Generating sitemap...');
        SitemapGenerator::create(config('app.url'))
            // Exclude admin or noisy routes if you want:
            ->shouldCrawl(function ($url) {
                foreach (['/admin', '/login'] as $exclude) {
                    if (str_contains($url, $exclude)) {
                        return false;
                    }
                }
                return true;
            })
            ->writeToFile(public_path('sitemap.xml'));

        $this->info('Done: public/sitemap.xml');
        return self::SUCCESS;
    }
}
