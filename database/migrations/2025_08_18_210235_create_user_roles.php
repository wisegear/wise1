<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->timestamps();
        });

        Schema::create('user_roles_pivot', function (Blueprint $table) {
            $table->id();
            $table->biginteger('role_id')->unsigned();
            $table->biginteger('user_id')->unsigned();
            $table->timestamps();

            //Create foreign keys

            $table->foreign('role_id')
                ->references('id')
                ->on('user_roles')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_roles_pivot');
    }
};
