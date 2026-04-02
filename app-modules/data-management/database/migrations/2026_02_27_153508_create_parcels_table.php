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
        Schema::create('dm_parcels', function (Blueprint $table) {
            $table->id();
            $table->string('parcel_code')->unique();
            $table->string('house_code')->nullable()->index();
            $table->json('geometry'); // Store GeoJSON
            $table->json('attributes'); // Store shapefile attributes
            $table->string('land_use_type')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('village')->nullable();
            $table->float('area_sqm')->nullable();
            $table->json('boundary_style')->nullable();
            $table->string('shap_file_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};
