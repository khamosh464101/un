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
        Schema::create('dm_house_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('materials_house_constructed');
            $table->json('issues_current_house');
            $table->string('issues_current_house_other')->nullable();
            $table->string('house_adequate_family_size');
            $table->string('house_adequate_family_size_no')->nullable();
            $table->text('house_adequate_family_size_no_other')->nullable();
            $table->string('made_housing_improvement');
            $table->string('made_housing_improvement_yes')->nullable();
            $table->string('received_humanitarian_assistance');
            $table->string('received_humanitarian_assistance_type')->nullable();
            $table->string('received_humanitarian_assistance_org')->nullable();
            $table->string('shelter_support_received');
            $table->string('shelter_support_received_yes')->nullable();
            $table->string('shelter_support_received_yes_other')->nullable();
            $table->string('rate_need_shelter_repair')->nullable();
            $table->text('surveyor_observation_current_house')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_house_conditions');
    }
};
