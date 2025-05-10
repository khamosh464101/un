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
        Schema::create('house_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('materials_house_constructed');
            $table->string('issues_current_house');
            $table->string('house_adequate_family_size');
            $table->string('house_adequate_family_size_no');
            $table->text('house_adequate_family_size_no_other');
            $table->string('made_housing_improvement');
            $table->string('made_housing_improvement_yes');
            $table->string('received_humanitarian_assistance');
            $table->string('received_humanitarian_assistance_type');
            $table->string('received_humanitarian_assistance_org');
            $table->string('shelter_support_received');
            $table->string('shelter_support_received_yes');
            $table->string('shelter_support_received_yes_other');
            $table->string('rate_need_shelter_repair');
            $table->text('surveyor_observation_current_house');
            $table->string('received_humanitarian_assistance_org');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('house_conditions');
    }
};
