<?php
namespace Modules\DataManagement\Services;

class QueryService
{
    public function getQuery(): array
    {
        return [
            'dstatus:id,title,color',
            'sourceInformation:id,submission_id,survey_province,district_name,surveyors_name,nahya_number,kbl_guzar_number,village_name,province_code,city_name,city_code,district_code,block_number,house_number,area_representative_name,area_representative_phone',
            'familyInformation:id,submission_id,number_families,household_size,hoh_disable,hof_or_interviewee,hof_ethnicity,province_origin,district_origin',
            'headFamily:id,submission_id,hoh_name,hoh_father_name,hoh_grandfather_name,hoh_phone_number,does_hoh_have_nic,hoh_nic_number,hoh_nic_photo,hoh_sex,hoh_age',
            'interviewwee:id,submission_id,interviewee_hof_relation,inter_name,inter_father_name,inter_grandfather_name,inter_phone_number,does_inter_have_nic,inter_nic_number,inter_nic_photo,inter_sex,inter_age',
            'composition:id,submission_id,female_0_1,male_0_1,female_1_5,male_1_5,female_6_12,male_6_12,female_13_17,male_13_17,female_18_30,male_18_30,female_30_60,male_30_60,female_60_above,male_60_above',
            'idp:id,submission_id,year_idp,idp_reason,idp_securtiy_reason,natural_disaster_reason,other_reason',
            'returnee:id,submission_id,year_returnee,migrate_country,migrate_country_other,migration_reason,migration_reason_security,migration_reason_natural_disaster,migration_reason_other,duration_Household_living_there,date_return_home_country,entry_borders,return_document_have,type_return_document,type_return_document_number,type_return_document_date,household_get_support_no,household_get_support,household_get_support_yes,organization_support,reason_return,return_reason_force,return_reason_voluntair',
            'extremelyVulnerableMember:id,submission_id,large_Household,disable_member,physical_disable,mental_disable,chronic_disable,drug_addicted,conditional_women,conditional_women_pregnant,conditional_women_breastfeeding_mother,conditional_women_widow',
            'accessCivilDocumentMale:id,submission_id,access_civil_documentation_male_tazkira,access_civil_documentation_male_birthcertificate,access_civil_documentation_male_marriagecertificate,access_civil_documentation_male_departationcard,access_civil_documentation_male_drivinglicense',
            'accessCivilDocumentFemale:id,submission_id,access_civil_documentation_female_tazkira,access_civil_documentation_female_birthcertificate,access_civil_documentation_female_marriagecertificate,access_civil_documentation_female_departationcard,access_civil_documentation_female_drivinglicense',
            'houseLandOwnership:id,submission_id,house_owner,inter_name_owner,inter_father_name_owner,inter_phone_number_owner,does_inter_have_nic_owner,inter_nic_number_owner,inter_nic_photo_owner,type_tenure_document,house_owner_myself,house_document_number,house_document_date,duration_lived_thishouse',
            'houseCondition:id,submission_id,materials_house_constructed,issues_current_house,issues_current_house_other,house_adequate_family_size,house_adequate_family_size_no,house_adequate_family_size_no_other,made_housing_improvement,made_housing_improvement_yes,received_humanitarian_assistance,received_humanitarian_assistance_type,received_humanitarian_assistance_org,shelter_support_received,shelter_support_received_yes,shelter_support_received_yes_other,rate_need_shelter_repair,surveyor_observation_current_house',
            'accessBasicService:id,submission_id,drinkingwater_main_source,type_water_source,water_source_distance,water_source_route_safe,water_source_route_safe_no,water_collect_person,water_quality,water_point_photo,type_toilet_facilities,access_sanitation_photo,access_education,access_school,type_school,nearest_school,access_school_university,access_school_madrasa,Household_members_attend_school_present,members_attend_school_no,members_attend_school_yes_boys,members_attend_school_yes_girls,Household_members_attend_madrasa_present_howmany,members_attend_madrasa_no,members_attend_madrasa_yes_boys,members_attend_madrasa_yes_girls,Household_members_attend_university_present,litrate_Household_member,number_male_child_Household,number_female_child_Household,access_education_photo,access_education_no,access_health_services,health_facilities_type,health_service_distance,health_service_distance_no,health_facility_have_female_staff,health_challanges,health_challanges_other,access_health_photo,type_access_road,access_road_photo,how_access_electricity,energy_cooking',
            'foodConsumptionScore:id,submission_id,days_inweek_eaten_cereal,days_inweek_eaten_pulse,days_inweek_eaten_vegetables,days_inweek_eaten_fruits,days_inweek_eaten_animal,days_inweek_eaten_dairy,days_inweek_eaten_oil,days_inweek_eaten_sugar,days_inweek_eaten_bread,food_cerel_source',
            'householdStrategyFood:id,submission_id,number_days_nothave_enough_food_less_expensive,number_days_nothave_enough_food_barrow,number_days_nothave_enough_food_limit_portion,number_days_nothave_enough_food_restrict_sonsumption,number_days_nothave_enough_food_reduce_meals,household_stocks_cereals,market_place,marketplace_distance',
            'communityAvailability:id,submission_id,community_avalibility,community_center_photo,community_org_female,community_org_male,Household_member_participate,Household_member_participate_yes',
            'livelihood:id,submission_id,Household_main_source_income,women_engagement_income,average_Household_monthly_income,improve_livelihoods,improve_livelihoods_other,debt,repaying_load_yes',
            'durableSolution:id,submission_id,future_families_preference,local_integration_details,local_integration_other,do_you_have_land,do_you_have_land_yes',
            'skillIdea:id,submission_id,members_have_skills,type_skills,type_skills_other,skills_want_learn',
            'resettlement:id,submission_id,relocate_another_place_by_government,reason_notwantto_relocate,relocate_minimum_condition',
            'recentAssistance:id,submission_id,receive_assistance,type_assistance,assistance_provided_by'
        ];
        // OLD WAY
                // $query = Submission::with([
        //     'sourceInformation', 
        //     'familyInformation', 
        //     'headFamily', 
        //     'interviewwee', 
        //     'composition',
        //     'idp',
        //     'returnee',
        //     'extremelyVulnerableMember',
        //     'accessCivilDocumentMale',
        //     'accessCivilDocumentFemale',
        //     'houseLandOwnership',
        //     'houseCondition',
        //     'accessBasicService',
        //     'foodConsumptionScore',
        //     'householdStrategyFood',
        //     'communityAvailability',
        //     'livelihood',
        //     'durableSolution',
        //     'skillIdea',
        //     'resettlement',
        //     'recentAssistance',
        //     'photoSection',
        // ]);

                // foreach ($this->filterable as $field) {
        //     $query->when($request->filled($field), function ($q) use ($field, $request) {
        //         if (Str::contains($field, '__')) {
        //             [$relation, $column] = explode('__', $field, 2);
        //             $q->whereHas($relation, function ($subQ) use ($column, $request, $field) {
        //                 $subQ->where($column, $request->input($field));
        //             });
        //         } else {
        //             $q->where($field, $request->input($field));
        //         }
        //     });
        // }
    }
}