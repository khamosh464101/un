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
        Schema::create('archive_dm_compositions', function (Blueprint $table) {
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
            $table->integer('f_female')->virtualAs('female_0_1 + female_1_5 + female_6_12 + female_13_17 + female_18_30 + female_30_60 + female_60_above');
            $table->integer('f_male')->virtualAs('male_0_1 + male_1_5 + male_6_12 + male_13_17 + male_18_30 + male_30_60 + male_60_above');
            $table->integer('f_total')->virtualAs('f_female + f_male');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_compositions');
    }
};
