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
        Schema::create('durable_solutins', function (Blueprint $table) {
            $table->id();
            $table->string('future_families_preference');
            $table->string('local_integration_details');
            $table->string('local_integration_other');
            $table->string('do_you_have_land');
            $table->integer('do_you_have_land_yes');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('durable_solutins');
    }
};
