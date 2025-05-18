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
        Schema::create('dm_house_land_ownerships', function (Blueprint $table) {
            $table->id();
            $table->string('house_owner');
            $table->string('inter_name_owner')->nullable();
            $table->string('inter_father_name_owner')->nullable();
            $table->string('inter_phone_number_owner')->nullable();
            $table->string('does_inter_have_nic_owner')->nullable();
            $table->string('inter_nic_number_owner')->nullable();
            $table->string('inter_nic_photo_owner')->nullable();
            $table->string('type_tenure_document');
            $table->string('house_owner_myself')->nullable();
            $table->string('house_document_number')->nullable();
            $table->date('house_document_date')->nullable();
            $table->string('duration_lived_thishouse')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_house_land_ownerships');
    }
};
