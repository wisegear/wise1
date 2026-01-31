<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PropertyDashboardHeroTest extends TestCase
{
    public function test_property_dashboard_hero_includes_search_button_before_outer_prime_link(): void
    {
        Cache::put('ew:propertyTypeSplitByYear:catA:v1', collect());
        Cache::put('ew:avgPriceByTypeByYear:catA:v1', collect());
        Cache::put('ew:newBuildSplitByYear:catA:v1', collect());
        Cache::put('ew:durationSplitByYear:catA:v1', collect());

        $view = $this->view('property.home', [
            'salesByYear' => collect(),
            'avgPriceByYear' => collect(),
            'ewP90' => collect(),
            'ewTop5' => collect(),
            'ewTopSalePerYear' => collect(),
            'ewTop3PerYear' => collect(),
            'sales24Labels' => [],
            'sales24Data' => [],
        ]);

        $searchUrl = route('property.search', absolute: false);

        $view->assertSee('Property Search');
        $view->assertSee($searchUrl, false);
        $view->assertSeeInOrder(['Property Search', 'Outer Prime London']);
    }
}
