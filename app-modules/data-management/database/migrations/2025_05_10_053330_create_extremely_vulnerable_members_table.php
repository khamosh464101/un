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
        Schema::create('extremely_vulnerable_members', function (Blueprint $table) {
            $table->id();
            $table->string('large_Household');
            $table->string('disable_member');
            $table->string('physical_disable');
            $table->string('mental_disable');
            $table->string('chronic_disable');
            $table->string('drug_addicted');
            $table->string('conditional_women');
            $table->string('conditional_women_pregnant');
            $table->string('conditional_women_breastfeeding_mother');
            $table->string('conditional_women_widow');
            $table->string('drug_addicted');
            $table->string('drug_addicted');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extremely_vulnerable_members');
    }
};
