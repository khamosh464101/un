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
        Schema::create('compositions', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('female_0_1');
            $table->tinyInteger('male_0_1');
            $table->tinyInteger('female_1_5');
            $table->tinyInteger('male_1_5');
            $table->tinyInteger('female_6_12');
            $table->tinyInteger('male_6_12');
            $table->tinyInteger('female_13_17');
            $table->tinyInteger('male_13_17');
            $table->tinyInteger('female_18_30');
            $table->tinyInteger('male_18_30');
            $table->tinyInteger('female_30_60');
            $table->tinyInteger('male_30_60');
            $table->tinyInteger('female_60_above');
            $table->tinyInteger('male_60_above');
            $table->tinyInteger('f_female');
            $table->tinyInteger('f_male');
            $table->tinyInteger('f_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compositions');
    }
};
