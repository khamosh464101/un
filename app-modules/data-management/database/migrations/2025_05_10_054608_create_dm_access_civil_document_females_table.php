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
        Schema::create('dm_access_civil_document_females', function (Blueprint $table) {
            $table->id();
            $table->integer('access_civil_documentation_female_tazkira');
            $table->integer('access_civil_documentation_female_birthcertificate');
            $table->integer('access_civil_documentation_female_marriagecertificate');
            $table->integer('access_civil_documentation_female_departationcard');
            $table->integer('access_civil_documentation_female_drivinglicense');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_access_civil_document_females');
    }
};
