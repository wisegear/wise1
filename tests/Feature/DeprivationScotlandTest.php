<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DeprivationScotlandTest extends TestCase
{
    public function test_scotland_show_handles_comma_separated_rank_values(): void
    {
        Cache::forget('simd.total_rank');

        $scotlandRowQuery = Mockery::mock();
        $scotlandRowQuery->shouldReceive('where')
            ->once()
            ->with('data_zone', 'S01008861')
            ->andReturnSelf();
        $scotlandRowQuery->shouldReceive('orderBy')
            ->once()
            ->with('postcode')
            ->andReturnSelf();
        $scotlandRowQuery->shouldReceive('first')
            ->once()
            ->andReturn((object) [
                'postcode' => 'EH1 1AA',
                'data_zone' => 'S01008861',
                'Council_area' => 'City of Edinburgh',
                'Intermediate_Zone' => 'Old Town',
                'rank' => '3,500',
                'decile' => '6',
                'income_rank' => '3,100',
                'employment_rank' => '3,200',
                'health_rank' => '3,300',
                'education_rank' => '3,400',
                'access_rank' => '3,500',
                'crime_rank' => '3,600',
                'housing_rank' => '3,700',
                'lat' => 55.9533,
                'long' => -3.1883,
            ]);

        $simdTotalQuery = Mockery::mock();
        $simdTotalQuery->shouldReceive('selectRaw')
            ->once()
            ->with("MAX(CAST(REPLACE(SIMD2020v2_Rank, ',', '') AS UNSIGNED)) as max_rank")
            ->andReturnSelf();
        $simdTotalQuery->shouldReceive('first')
            ->once()
            ->andReturn((object) ['max_rank' => 6976]);

        DB::shouldReceive('table')
            ->once()
            ->with('v_postcode_deprivation_scotland')
            ->andReturn($scotlandRowQuery);

        DB::shouldReceive('table')
            ->once()
            ->with('simd2020')
            ->andReturn($simdTotalQuery);

        $response = $this->get(route('deprivation.scot.show', ['dz' => 'S01008861']));

        $response->assertStatus(200);
        $response->assertSee('Rank: 3,500 of 6,976');
    }
}
