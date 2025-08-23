<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prime_postcodes', function (Blueprint $table) {
            $table->id();
            $table->string('postcode', 10);
            $table->enum('category', ['Prime Central', 'Ultra Prime']);
            $table->timestamps();
        });

        // Seed initial data
        DB::table('prime_postcodes')->insert([
            // Prime Central
            ['postcode' => 'W1', 'category' => 'Prime Central'],
            ['postcode' => 'SW1', 'category' => 'Prime Central'],
            ['postcode' => 'W8', 'category' => 'Prime Central'],
            ['postcode' => 'W11', 'category' => 'Prime Central'],
            ['postcode' => 'SW3', 'category' => 'Prime Central'],
            ['postcode' => 'SW7', 'category' => 'Prime Central'],
            ['postcode' => 'NW8', 'category' => 'Prime Central'],

            // Ultra Prime
            ['postcode' => 'W1K', 'category' => 'Ultra Prime'],
            ['postcode' => 'SW1X', 'category' => 'Ultra Prime'],
            ['postcode' => 'W1J', 'category' => 'Ultra Prime'],
            ['postcode' => 'WC2', 'category' => 'Ultra Prime'],
            ['postcode' => 'SW1W', 'category' => 'Ultra Prime'],
            ['postcode' => 'W1B', 'category' => 'Ultra Prime'],
            ['postcode' => 'SW7', 'category' => 'Ultra Prime'],

        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('prime_postcodes');
    }
};
