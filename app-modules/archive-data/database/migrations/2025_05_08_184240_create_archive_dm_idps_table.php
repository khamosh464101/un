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
        Schema::create('archive_dm_idps', function (Blueprint $table) {
            $table->id();
            $table->string('year_idp');
            $table->string('idp_reason');
            $table->string('idp_securtiy_reason')->nullable();
            $table->string('natural_disaster_reason')->nullable();
            $table->string('other_reason')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_idps');
    }
};
