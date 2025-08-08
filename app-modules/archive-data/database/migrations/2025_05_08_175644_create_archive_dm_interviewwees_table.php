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
        Schema::create('archive_dm_interviewwees', function (Blueprint $table) {
            $table->id();
            $table->string('interviewee_hof_relation')->nullable();
            $table->string('inter_name');
            $table->string('inter_father_name');
            $table->string('inter_grandfather_name');
            $table->string('inter_phone_number');
            $table->string('does_inter_have_nic');
            $table->string('inter_nic_number')->nullable();
            $table->string('inter_nic_photo')->nullable();
            $table->string('inter_sex');
            $table->string('inter_age');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_dm_interviewwees');
    }
};
