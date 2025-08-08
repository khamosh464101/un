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
        Schema::create('dm_source_information', function (Blueprint $table) {
            $table->id();
            $table->string('survey_province');
            $table->string('district_name');
            $table->string('surveyors_code')->nullable();
            $table->string('surveyors_name')->nullable();
            $table->string('nahya_number')->nullable();
            $table->string('kbl_guzar_number')->nullable();
            $table->string('village_name')->nullable();
            $table->string('province_code')->nullable();
            $table->string('city_name')->nullable();
            $table->string('city_code')->nullable();
            $table->string('district_code')->nullable();
            $table->string('code_number')->nullable();
            $table->string('block_number');
            $table->string('house_number');
            $table->string('area_representative_name');
            $table->string('area_representative_phone');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_source_information');
    }
};
