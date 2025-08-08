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
        Schema::create('dm_livelihoods', function (Blueprint $table) {
            $table->id();
            $table->string('Household_main_source_income');
            $table->string('women_engagement_income');
            $table->string('average_Household_monthly_income');
            $table->string('improve_livelihoods');
            $table->string('improve_livelihoods_other')->nullable();
            $table->string('debt');
            $table->string('repaying_load_yes')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_livelihoods');
    }
};
