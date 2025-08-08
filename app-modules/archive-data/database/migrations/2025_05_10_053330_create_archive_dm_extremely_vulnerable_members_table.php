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
        Schema::create('archive_dm_extremely_vulnerable_members', function (Blueprint $table) {
            $table->id();
            $table->string('large_Household');
            $table->string('disable_member');
            $table->string('physical_disable')->nullable();
            $table->string('mental_disable')->nullable();
            $table->string('chronic_disable')->nullable();
            $table->string('drug_addicted')->nullable();
            $table->string('conditional_women');
            $table->string('conditional_women_pregnant')->nullable();
            $table->string('conditional_women_breastfeeding_mother')->nullable();
            $table->string('conditional_women_widow')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_extremely_vulnerable_members');
    }
};
