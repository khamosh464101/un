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
        Schema::create('dm_import_format_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id');
            $table->string('kobo_form_field_name');
            $table->string('excel_file_column_name');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_import_format_maps');
    }
};


// project has one format

// format has many excel file
// format has many connections 
