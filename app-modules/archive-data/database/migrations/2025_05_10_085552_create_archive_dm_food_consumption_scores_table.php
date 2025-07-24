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
        Schema::create('archive_dm_food_consumption_scores', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('days_inweek_eaten_cereal');
            $table->tinyInteger('days_inweek_eaten_pulse');
            $table->tinyInteger('days_inweek_eaten_vegetables');
            $table->tinyInteger('days_inweek_eaten_fruits');
            $table->tinyInteger('days_inweek_eaten_animal');
            $table->tinyInteger('days_inweek_eaten_dairy');
            $table->tinyInteger('days_inweek_eaten_oil');
            $table->tinyInteger('days_inweek_eaten_sugar');
            $table->tinyInteger('days_inweek_eaten_bread');
            $table->string('food_cerel_source');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_food_consumption_scores');
    }
};
