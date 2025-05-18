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
        Schema::create('dm_land_ownership_documents', function (Blueprint $table) {
            $table->id();
            $table->string('house_document_photo');
            $table->foreignId('dm_house_land_ownership_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_land_ownership_documents');
    }
};
