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
        Schema::create('dm_type_return_document_photos', function (Blueprint $table) {
            $table->id();
            $table->string('type_return_document_photo');
            $table->foreignId('dm_returnee_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_type_return_document_photos');
    }
};
