<?php

namespace Tests\Feature;

use App\Models\MortgageApproval;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EconomicDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureDashboardTablesExist();
    }

    protected function ensureDashboardTablesExist(): void
    {
        if (! Schema::hasTable('interest_rates')) {
            Schema::create('interest_rates', function (Blueprint $table) {
                $table->id();
                $table->date('effective_date');
                $table->decimal('rate', 6, 3)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('inflation_cpih_monthly')) {
            Schema::create('inflation_cpih_monthly', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->decimal('value', 8, 3)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wage_growth_monthly')) {
            Schema::create('wage_growth_monthly', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->decimal('three_month_avg_yoy', 8, 3)->nullable();
                $table->decimal('single_month_yoy', 8, 3)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('unemployment_monthly')) {
            Schema::create('unemployment_monthly', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->bigInteger('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mortgage_approvals')) {
            Schema::create('mortgage_approvals', function (Blueprint $table) {
                $table->id();
                $table->string('series_code', 32);
                $table->date('period');
                $table->unsignedInteger('value')->nullable();
                $table->string('unit', 16)->nullable();
                $table->string('source', 64)->default('BoE');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mlar_arrears')) {
            Schema::create('mlar_arrears', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('year')->nullable();
                $table->string('quarter', 2)->nullable();
                $table->string('description')->nullable();
                $table->string('band')->nullable();
                $table->decimal('value', 8, 3)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hpi_monthly')) {
            Schema::create('hpi_monthly', function (Blueprint $table) {
                $table->id();
                $table->string('AreaCode', 16)->nullable();
                $table->date('Date');
                $table->unsignedInteger('AveragePrice')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_mortgage_approvals_sparkline_uses_house_purchase_series_only(): void
    {
        MortgageApproval::query()->create([
            'series_code' => 'LPMVTVX',
            'period' => '2025-01-01',
            'value' => 100,
            'unit' => 'count',
            'source' => 'BoE',
        ]);

        MortgageApproval::query()->create([
            'series_code' => 'LPMVTVX',
            'period' => '2025-02-01',
            'value' => 110,
            'unit' => 'count',
            'source' => 'BoE',
        ]);

        MortgageApproval::query()->create([
            'series_code' => 'LPMVTVX',
            'period' => '2025-03-01',
            'value' => 120,
            'unit' => 'count',
            'source' => 'BoE',
        ]);

        MortgageApproval::query()->create([
            'series_code' => 'LPMB4B3',
            'period' => '2025-03-01',
            'value' => 200,
            'unit' => 'count',
            'source' => 'BoE',
        ]);

        MortgageApproval::query()->create([
            'series_code' => 'LPMB4B4',
            'period' => '2025-03-01',
            'value' => 300,
            'unit' => 'count',
            'source' => 'BoE',
        ]);

        MortgageApproval::query()->create([
            'series_code' => 'LPMB3C8',
            'period' => '2025-04-01',
            'value' => 999,
            'unit' => 'count',
            'source' => 'BoE',
        ]);

        $response = $this->get(route('economic.dashboard', absolute: false));

        $response->assertOk();

        $sparklines = $response->viewData('sparklines');
        $approvals = $response->viewData('approvals');

        $this->assertSame([100.0, 110.0, 120.0], $sparklines['approvals']['values']);
        $this->assertSame(120.0, (float) $approvals->value);
        $this->assertSame('2025-03-01', Carbon::parse($approvals->period)->toDateString());
    }
}
