<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epc_certificates', function (Blueprint $table) {
            $table->unique('lmk_key', 'uq_epc_certificates_lmk_key');
        });
    }

    public function down(): void
    {
        Schema::table('epc_certificates', function (Blueprint $table) {
            $table->dropUnique('uq_epc_certificates_lmk_key');
        });
    }
};
