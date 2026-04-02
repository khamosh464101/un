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
        Schema::create('symbology_settings', function (Blueprint $table) {
            $table->id();
             $table->string('land_use_type')->unique();
            $table->string('fill_color')->default('#0000FF');
            $table->string('border_color')->default('#000000');
            $table->float('fill_opacity')->default(0.6);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('symbology_settings');
    }
};
