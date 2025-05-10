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
        Schema::create('photo_sections', function (Blueprint $table) {
            $table->id();
            $table->string('field_name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->string('photo_interviewee');
            $table->string('photo_house_building');
            $table->string('photo_house_door');
            $table->string('photo_enovirment');
            $table->string('photo_other');
            $table->text('remarks');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_sections');
    }
};
