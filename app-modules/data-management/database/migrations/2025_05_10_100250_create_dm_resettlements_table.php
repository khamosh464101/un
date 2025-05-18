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
        Schema::create('dm_resettlements', function (Blueprint $table) {
            $table->id();
            $table->string('relocate_another_place_by_government');
            $table->string('reason_notwantto_relocate')->nullable();
            $table->string('relocate_minimum_condition')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_resettlements');
    }
};
