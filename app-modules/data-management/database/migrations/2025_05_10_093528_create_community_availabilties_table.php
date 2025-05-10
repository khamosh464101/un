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
        Schema::create('community_availabilties', function (Blueprint $table) {
            $table->id();
            $table->string('community_avalibility');
            $table->string('community_center_photo'); 
            $table->string('community_org_female'); 
            $table->string('community_org_male'); 
            $table->string('Household_member_participate');  
            $table->string('Household_member_participate_yes');     
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_availabilties');
    }
};
