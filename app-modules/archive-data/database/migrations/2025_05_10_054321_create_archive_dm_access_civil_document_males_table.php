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
        Schema::create('archive_dm_access_civil_document_males', function (Blueprint $table) {
            $table->id();
            $table->integer('access_civil_documentation_male_tazkira');
            $table->integer('access_civil_documentation_male_birthcertificate');
            $table->integer('access_civil_documentation_male_marriagecertificate');
            $table->integer('access_civil_documentation_male_departationcard');
            $table->integer('access_civil_documentation_male_drivinglicense');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_access_civil_document_males');
    }
};
