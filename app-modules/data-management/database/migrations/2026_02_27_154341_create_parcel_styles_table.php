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
        Schema::create('dm_parcel_styles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default');
            
            // Selected parcel style (the target house)
            $table->string('selected_color')->default('#FF0000'); // Red
            $table->integer('selected_weight')->default(3); // Border thickness in pixels
            $table->enum('selected_style', ['solid', 'dashed', 'dotted'])->default('solid');
            $table->float('selected_opacity')->default(1.0); // 100% opacity
            
            // Other parcels style (50% white mask effect)
            $table->string('other_color')->default('#CCCCCC'); // Light grey
            $table->integer('other_weight')->default(1); // Thin border
            $table->enum('other_style', ['solid', 'dashed', 'dotted'])->default('solid');
            $table->float('other_opacity')->default(0.3); // 30% opacity (70% white mask)
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_parcel_styles');
    }
};
