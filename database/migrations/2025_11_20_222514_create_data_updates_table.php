<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_updates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "MLAR Arrears", "Land Registry PPD"
            $table->date('last_updated_at')->nullable();
            $table->date('next_update_due_at')->nullable();
            $table->text('notes')->nullable();        // free text for reminders
            $table->string('data_link')->nullable();  // link to data source or internal page
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_updates');
    }
};
