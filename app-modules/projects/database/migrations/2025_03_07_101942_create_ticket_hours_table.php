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
        Schema::create('ticket_hours', function (Blueprint $table) {
            $table->id();
            $table->float('value');
            $table->string('title');
            $table->text('comment')->nullable();
            $table->foreignId('ticket_id');
            $table->foreignId('user_id');
            
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_hours');
    }
};
