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
        Schema::create('family_information', function (Blueprint $table) {
            $table->id();
            $table->integer('number_families');
            $table->integer('household_size');
            $table->string('hoh_disable');
            $table->string('hof_or_interviewee');
            $table->integer('number_families');
            $table->integer('number_families');
            $table->integer('number_families');
            $table->integer('number_families');
            $table->integer('number_families');
            $table->integer('number_families');
            $table->integer('number_families');
            $table->integer('number_families');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_information');
    }
};
