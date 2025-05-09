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
        Schema::create('returnees', function (Blueprint $table) {
            $table->id();
            $table->string('year_returnee');
            $table->string('migrate_country');
            // if select other country then enter then name
            $table->string('migrate_country_other');
            $table->string('migration_reason');
            $table->string('migration_reason_security');
            $table->string('migration_reason_natural_disaster');
            $table->string('migration_reason_other');

            
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returnees');
    }
};
