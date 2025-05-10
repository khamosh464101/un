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
        Schema::create('access_basic_services', function (Blueprint $table) {
            $table->id();
            $table->string('drinkingwater_main_source');
            $table->string('type_water_source');
            $table->string('water_source_distance');
            $table->string('water_source_route_safe');
            $table->string('water_source_route_safe_no');
            $table->string('water_collect_person');
            $table->string('water_quality');
            $table->string('water_point_photo');
            $table->string('type_toilet_facilities');
            $table->string('access_sanitation_photo');
            
            $table->string('access_education');
            $table->string('access_school');
            $table->string('type_school');
            $table->string('nearest_school');
            $table->string('access_school_university');
            $table->string('access_school_madrasa');
            $table->string('Household_members_attend_school_present');
            $table->string('members_attend_school_no');
            $table->string('members_attend_school_yes_boys');
            $table->string('members_attend_school_yes_girls');
            $table->string('Household_members_attend_madrasa_present_howmany');
            $table->string('members_attend_madrasa_no');
            $table->string('members_attend_madrasa_yes_boys');
            $table->string('members_attend_madrasa_yes_girls');
            $table->string('Household_members_attend_university_present');
            $table->string('litrate_Household_member');
            $table->string('number_male_child_Household');
            $table->string('access_education_photo');
            $table->string('access_education_no');

            $table->string('access_health_services');
            $table->string('health_facilities_type');
            $table->string('health_service_distance');
            $table->string('health_service_distance_no');
            $table->string('health_facility_have_female_staff');
            $table->string('health_challanges');
            $table->string('health_challanges_other');
            $table->string('access_health_photo');

            $table->string('type_access_road');
            $table->string('access_road_photo');

            $table->string('how_access_electricity');
            $table->string('energy_cooking');
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_basic_services');
    }
};
