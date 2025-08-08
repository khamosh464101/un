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
        Schema::create('archive_dm_photo_sections', function (Blueprint $table) {
            $table->id();
            $table->string('field_name')->nullable();
            $table->decimal('latitude', 12, 10)->nullable();
            $table->decimal('longitude', 12, 10)->nullable();
            $table->decimal('altitude', 15, 10)->nullable();
            $table->float('accuracy')->nullable();
            $table->string('photo_interviewee');
            $table->string('photo_house_building');
            $table->string('photo_house_door');
            $table->string('photo_enovirment');
            $table->string('photo_other')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_photo_sections');
    }
};
