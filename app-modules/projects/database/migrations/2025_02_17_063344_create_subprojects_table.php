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
        Schema::create('subprojects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('budget', 12, 2);
            $table->date('announcement_date');
            $table->date('date_of_contract')->nullable();
            $table->integer('number_of_months')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('partner_id');
            $table->foreignId('project_id');
            $table->foreignId('subproject_type_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subprojects');
    }
};
