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
        Schema::create('archive_dm_recent_assistances', function (Blueprint $table) {
            $table->id();
            $table->string('receive_assistance');
            $table->string('type_assistance')->nullable();
            $table->string('assistance_provided_by')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_recent_assistances');
    }
};
