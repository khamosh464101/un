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
        Schema::create('dm_bulk_download_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->unsignedBigInteger('submission_id');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->integer('progress')->default(0);
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['batch_id', 'status']);
            $table->index(['batch_id', 'submission_id']);
            $table->index('updated_at');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_bulk_download_items');
    }
};
