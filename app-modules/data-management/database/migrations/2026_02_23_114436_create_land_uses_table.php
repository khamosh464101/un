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
        Schema::create('dm_land_uses', function (Blueprint $table) {
            $table->id();
            $table->string('land_use_type');
            $table->json('geometry'); // Store GeoJSON
            $table->json('properties'); // Store all shapefile attributes
            $table->string('fill_color')->default('#0000FF');
            $table->string('border_color')->default('#000000');
            $table->float('fill_opacity')->default(0.6);
            $table->json('symbology_rules')->nullable(); // Store symbology rules
            $table->string('shap_file_name');
            $table->timestamps();
        });    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dmland_uses');
    }
};
