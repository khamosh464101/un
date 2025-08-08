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
        Schema::create('archive_dm_household_strategy_food', function (Blueprint $table) {
            $table->id();
            $table->integer('number_days_nothave_enough_food_less_expensive');
            $table->integer('number_days_nothave_enough_food_barrow');
            $table->integer('number_days_nothave_enough_food_limit_portion');
            $table->integer('number_days_nothave_enough_food_restrict_sonsumption');
            $table->integer('number_days_nothave_enough_food_reduce_meals');
            $table->string('household_stocks_cereals');
            $table->string('market_place');
            $table->string('marketplace_distance')->nullable();    
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_household_strategy_food');
    }
};
