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
        Schema::create('dm_access_basic_services', function (Blueprint $table) {
            $table->id();
            $table->string('drinkingwater_main_source');
            $table->string('type_water_source');
            $table->string('water_source_distance')->nullable();
            $table->string('water_source_route_safe')->nullable();
            $table->string('water_source_route_safe_no')->nullable();
            $table->string('water_collect_person')->nullable();
            $table->string('water_quality');
            $table->string('water_point_photo');
            $table->string('type_toilet_facilities');
            $table->string('access_sanitation_photo');
            
            $table->string('access_education');
            $table->string('access_school')->nullable();
            $table->string('type_school')->nullable();
            $table->string('nearest_school')->nullable();
            $table->string('access_school_university')->nullable();
            $table->string('access_school_madrasa')->nullable();
            $table->string('Household_members_attend_school_present')->nullable();
            $table->string('members_attend_school_no')->nullable();
            $table->string('members_attend_school_yes_boys')->nullable();
            $table->string('members_attend_school_yes_girls')->nullable();
            $table->string('Household_members_attend_madrasa_present_howmany')->nullable();
            $table->string('members_attend_madrasa_no')->nullable();
            $table->string('members_attend_madrasa_yes_boys')->nullable();
            $table->string('members_attend_madrasa_yes_girls')->nullable();
            $table->string('Household_members_attend_university_present')->nullable();
            $table->string('litrate_Household_member')->nullable();
            $table->string('number_male_child_Household')->nullable();
            $table->string('number_female_child_Household')->nullable();
            $table->string('access_education_photo')->nullable();
            $table->string('access_education_no')->nullable();

            $table->string('access_health_services');
            $table->string('health_facilities_type')->nullable();
            $table->string('health_service_distance')->nullable();
            $table->string('health_service_distance_no')->nullable();
            $table->string('health_facility_have_female_staff')->nullable();
            $table->string('health_challanges');
            $table->string('health_challanges_other')->nullable();
            $table->string('access_health_photo')->nullable();

            $table->string('type_access_road');
            $table->string('access_road_photo');

            $table->string('how_access_electricity');
            $table->string('energy_cooking')->nullable();
            $table->foreignId('submission_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_access_basic_services');
    }
};
