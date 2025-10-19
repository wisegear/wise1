<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lsoa_2011_to_2021', function (Blueprint $t) {
            // Same column order as your CSV screenshot
            $t->char('LSOA11CD', 9);       // E01013149
            $t->string('LSOA11NM', 40);    // Adur 001A
            $t->char('LSOA21CD', 9);       // E01013149 (same code or new one)
            $t->string('LSOA21NM', 40);    // Adur 001A
            $t->char('CHGIND', 1)->nullable();   // Change indicator (U = Unchanged)
            $t->char('LAD22CD', 9)->nullable();  // Local authority district code (E07000223)
            $t->string('LAD22NM', 50)->nullable(); // Local authority name (Adur)
            $t->string('LAD22NMW', 50)->nullable(); // Welsh name (null for England)
            $t->unsignedInteger('ObjectId')->nullable(); // optional numeric ID

            // Composite key for safety (some 1:N mappings exist)
            $t->primary(['LSOA11CD', 'LSOA21CD']);

            // Helpful indexes
            $t->index('LSOA11CD');
            $t->index('LSOA21CD');
            $t->index('LAD22CD');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lsoa_2011_to_2021');
    }
};
