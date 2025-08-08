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
        Schema::create('archive_dm_family_information', function (Blueprint $table) {
            $table->id();
            $table->integer('number_families')->nullable();
            $table->integer('household_size');
            $table->string('hoh_disable');
            $table->string('hof_or_interviewee');
            $table->string('hof_ethnicity');
            $table->string('province_origin');
            $table->string('district_origin');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_family_information');
    }
};
