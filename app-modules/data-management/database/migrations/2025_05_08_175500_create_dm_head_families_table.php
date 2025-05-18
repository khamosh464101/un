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
        Schema::create('dm_head_families', function (Blueprint $table) {
            $table->id();
            $table->string('hoh_name');
            $table->string('hoh_father_name');
            $table->string('hoh_grandfather_name');
            $table->string('hoh_phone_number');
            $table->string('does_hoh_have_nic');
            $table->string('hoh_nic_number')->nullable();
            $table->string('hoh_nic_photo')->nullable();
            $table->string('hoh_sex');
            $table->string('hoh_age');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_head_families');
    }
};
