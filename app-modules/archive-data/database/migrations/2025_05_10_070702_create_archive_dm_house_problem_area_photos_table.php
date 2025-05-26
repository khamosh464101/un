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
        Schema::create('archive_dm_house_problem_area_photos', function (Blueprint $table) {
            $table->id();
            $table->string('current_house_problem_title');
            $table->string('current_house_problem_photo');
            $table->foreignId('dm_house_condition_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_house_problem_area_photos');
    }
};
