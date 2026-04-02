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
        Schema::create('dm_bulk_download_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->unsignedBigInteger('submission_id')->nullable();
            $table->string('level'); // info, warning, error
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();
            
            $table->index(['batch_id', 'level']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_bulk_download_logs');
    }
};
