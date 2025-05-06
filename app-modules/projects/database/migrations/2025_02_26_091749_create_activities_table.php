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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('activity_number')->nullable();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('project_id');
            $table->foreignId('activity_status_id');
            $table->foreignId('activity_type_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
