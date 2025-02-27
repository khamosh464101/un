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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('code');
            $table->decimal("budget", 15, 5);
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('kobo_project_id')->nullable();
            $table->foreignId('donor_id');
            $table->foreignId('program_id');
            $table->foreignId('project_status_id');
            $table->foreignId('manager_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
