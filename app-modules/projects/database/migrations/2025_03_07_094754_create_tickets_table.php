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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('ticket_number')->nullable();
            $table->text('description')->nullable();
            $table->decimal('estimation', 6, 2);
            $table->datetime('deadline');
            $table->integer('order')->default(0);
            $table->foreignId('owner_id');
            $table->foreignId('responsible_id');
            $table->foreignId('ticket_status_id');
            $table->foreignId('ticket_type_id');
            $table->foreignId('ticket_priority_id');
            $table->foreignId('activity_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
