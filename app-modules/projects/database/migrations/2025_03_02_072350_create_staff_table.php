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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position_title');
            $table->string('personal_email')->unique()->nullable();
            $table->string('official_email')->unique();
            $table->string('phone1');
            $table->string('phone2')->nullable();
            $table->string('photo')->nullable();
            $table->string('duty_station');
            $table->date('date_of_joining')->nullable();
            $table->date('end_of_contract')->nullable();
            $table->text('about')->nullable();
            $table->string('gender')->default('Female');
            $table->foreignId('staff_status_id');
            $table->foreignId('staff_contract_type_id')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
