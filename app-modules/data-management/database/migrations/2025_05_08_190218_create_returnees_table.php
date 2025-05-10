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
            $table->string('duration_Household_living_there');
            $table->date("date_return_home_country");
            $table->string('entry_borders');
            $table->string('return_document_have');
            $table->string('type_return_document');
            $table->string('type_return_document_number');
            $table->date("type_return_document_date");
            $table->text("household_get_support_no");
            $table->string('household_get_support');
            $table->string('household_get_support_yes');
            $table->string('organization_support');
            $table->string('reason_return');
            $table->string('return_reason_force');
            $table->string('return_reason_voluntair');
            $table->foreignId('submission_id');
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
