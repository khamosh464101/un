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
        Schema::create('dm_submissions', function (Blueprint $table) {
            $table->id();
            $table->integer('_id')->nullable();
            $table->string('_uuid')->nullable();
            $table->date('today');
            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();
            $table->string('__version__')->nullable();
            $table->dateTime('_submission_time')->nullable();
            // itor_agreement
            $table->string('consent');
            // 4 displacement
            $table->string('status');
            $table->foreignId('dm_form_id');
            $table->foreignId('submission_status_id');
            $table->timestamps();
        });
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_submissions');
    }
};
