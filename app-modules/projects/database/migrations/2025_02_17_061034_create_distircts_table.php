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
        Schema::create('distircts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_fa');
            $table->string('name_pa');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('code')->nullable();
            $table->foreignId('province_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distircts');
    }
};
