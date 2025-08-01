<?php
namespace Modules\DataManagement\Services;

class FilterableService
{
    public function getFilterable(): array
    {
        return [
            'dstatus__title',
        'sourceInformation__survey_province',
        'sourceInformation__province_code',
        'sourceInformation__city_name',
        'sourceInformation__city_code',
        'sourceInformation__district_name',
        'sourceInformation__district_code',
        'sourceInformation__nahya_number',
        'sourceInformation__kbl_guzar_number',
        'sourceInformation__village_name',
        'sourceInformation__block_number',
        'sourceInformation__house_number',
        'status',
        'sourceInformation__surveyors_name',
        'sourceInformation__area_representative_name',
        'sourceInformation__area_representative_phone',
        'familyInformation__number_families',
        'familyInformation__household_size',
        'familyInformation__hoh_disable',
        'familyInformation__hof_or_interviewee',
        'familyInformation__hof_ethnicity',
        'familyInformation__province_origin',
        'familyInformation__district_origin',

        'headFamily__hoh_name',
        'headFamily__hoh_father_name',
        'headFamily__hoh_grandfather_name',
        'headFamily__hoh_phone_number',
        'headFamily__does_hoh_have_nic',
        'headFamily__hoh_nic_number',
        'headFamily__hoh_nic_photo',
        'headFamily__hoh_sex',
        'headFamily__hoh_age',

        'interviewwee__interviewee_hof_relation',
        'interviewwee__inter_name',
        'interviewwee__inter_father_name',
        'interviewwee__inter_grandfather_name',
        'interviewwee__inter_phone_number',
        'interviewwee__does_inter_have_nic',
        'interviewwee__inter_nic_number',
        'interviewwee__inter_nic_photo',
        'interviewwee__inter_sex',
        'interviewwee__inter_age',
        
        'composition__female_0_1',
        'composition__male_0_1',
        'composition__female_1_5',
        'composition__male_1_5',
        'composition__female_6_12',
        'composition__male_6_12',
        'composition__female_13_17',
        'composition__male_13_17',
        'composition__female_18_30',
        'composition__male_18_30',
        'composition__female_30_60',
        'composition__male_30_60',
        'composition__female_60_above',
        'composition__male_60_above',

        'idp__year_idp',
        'idp__idp_reason',
        'idp__idp_securtiy_reason',
        'idp__natural_disaster_reason',
        'idp__other_reason',

        'returnee__year_returnee',
        'returnee__migrate_country',
        'returnee__migrate_country_other',
        'returnee__migration_reason',
        'returnee__migration_reason_security',
        'returnee__migration_reason_natural_disaster',
        'returnee__migration_reason_other',
        'returnee__duration_Household_living_there',
        'returnee__date_return_home_country',
        'returnee__entry_borders',
        'returnee__return_document_have',
        'returnee__type_return_document',
        'returnee__type_return_document_number',
        'returnee__type_return_document_date',
        'returnee__household_get_support_no',
        'returnee__household_get_support',
        'returnee__household_get_support_yes',
        'returnee__organization_support',
        'returnee__reason_return',
        'returnee__return_reason_force',
        'returnee__return_reason_voluntair',
        
        'extremelyVulnerableMember__large_Household',
        'extremelyVulnerableMember__disable_member',
        'extremelyVulnerableMember__physical_disable',
        'extremelyVulnerableMember__mental_disable',
        'extremelyVulnerableMember__chronic_disable',
        'extremelyVulnerableMember__drug_addicted',
        'extremelyVulnerableMember__conditional_women',
        'extremelyVulnerableMember__conditional_women_pregnant',
        'extremelyVulnerableMember__conditional_women_breastfeeding_mother',
        'extremelyVulnerableMember__conditional_women_widow',

        'accessCivilDocumentMale__access_civil_documentation_male_tazkira',
        'accessCivilDocumentMale__access_civil_documentation_male_birthcertificate',
        'accessCivilDocumentMale__access_civil_documentation_male_marriagecertificate',
        'accessCivilDocumentMale__access_civil_documentation_male_departationcard',
        'accessCivilDocumentMale__access_civil_documentation_male_drivinglicense',

        'accessCivilDocumentFemale__access_civil_documentation_female_tazkira',
        'accessCivilDocumentFemale__access_civil_documentation_female_birthcertificate',
        'accessCivilDocumentFemale__access_civil_documentation_female_marriagecertificate',
        'accessCivilDocumentFemale__access_civil_documentation_female_departationcard',
        'accessCivilDocumentFemale__access_civil_documentation_female_drivinglicense',

        'houseLandOwnership__house_owner',
        'houseLandOwnership__inter_name_owner',
        'houseLandOwnership__inter_father_name_owner',
        'houseLandOwnership__inter_phone_number_owner',
        'houseLandOwnership__does_inter_have_nic_owner',
        'houseLandOwnership__inter_nic_number_owner',
        'houseLandOwnership__inter_nic_photo_owner',
        'houseLandOwnership__type_tenure_document',
        'houseLandOwnership__house_owner_myself',
        'houseLandOwnership__house_document_number',
        'houseLandOwnership__house_document_date',
        'houseLandOwnership__duration_lived_thishouse',

        'houseCondition__materials_house_constructed',
        'houseCondition__issues_current_house',
        'houseCondition__issues_current_house_other',
        'houseCondition__house_adequate_family_size',
        'houseCondition__house_adequate_family_size_no',
        'houseCondition__house_adequate_family_size_no_other',
        'houseCondition__made_housing_improvement',
        'houseCondition__made_housing_improvement_yes',
        'houseCondition__received_humanitarian_assistance',
        'houseCondition__received_humanitarian_assistance_type',
        'houseCondition__received_humanitarian_assistance_org',
        'houseCondition__shelter_support_received',
        'houseCondition__shelter_support_received_yes',
        'houseCondition__shelter_support_received_yes_other',
        'houseCondition__rate_need_shelter_repair',
        'houseCondition__surveyor_observation_current_house',

        'accessBasicService__drinkingwater_main_source',
        'accessBasicService__type_water_source',
        'accessBasicService__water_source_distance',
        'accessBasicService__water_source_route_safe',
        'accessBasicService__water_source_route_safe_no',
        'accessBasicService__water_collect_person',
        'accessBasicService__water_quality',
        'accessBasicService__water_point_photo',
        'accessBasicService__type_toilet_facilities',
        'accessBasicService__access_sanitation_photo',
        
        'accessBasicService__access_education',
        'accessBasicService__access_school',
        'accessBasicService__type_school',
        'accessBasicService__nearest_school',
        'accessBasicService__access_school_university',
        'accessBasicService__access_school_madrasa',
        'accessBasicService__Household_members_attend_school_present',
        'accessBasicService__members_attend_school_no',
        'accessBasicService__members_attend_school_yes_boys',
        'accessBasicService__members_attend_school_yes_girls',
        'accessBasicService__Household_members_attend_madrasa_present_howmany',
        'accessBasicService__members_attend_madrasa_no',
        'accessBasicService__members_attend_madrasa_yes_boys',
        'accessBasicService__members_attend_madrasa_yes_girls',
        'accessBasicService__Household_members_attend_university_present',
        'accessBasicService__litrate_Household_member',
        'accessBasicService__number_male_child_Household',
        'accessBasicService__number_female_child_Household',
        'accessBasicService__access_education_photo',
        'accessBasicService__access_education_no',

        'accessBasicService__access_health_services',
        'accessBasicService__health_facilities_type',
        'accessBasicService__health_service_distance',
        'accessBasicService__health_service_distance_no',
        'accessBasicService__health_facility_have_female_staff',
        'accessBasicService__health_challanges',
        'accessBasicService__health_challanges_other',
        'accessBasicService__access_health_photo',

        'accessBasicService__type_access_road',
        'accessBasicService__access_road_photo',

        'accessBasicService__how_access_electricity',
        'accessBasicService__energy_cooking',

        'foodConsumptionScore__days_inweek_eaten_cereal',
        'foodConsumptionScore__days_inweek_eaten_pulse',
        'foodConsumptionScore__days_inweek_eaten_vegetables',
        'foodConsumptionScore__days_inweek_eaten_fruits',
        'foodConsumptionScore__days_inweek_eaten_animal',
        'foodConsumptionScore__days_inweek_eaten_dairy',
        'foodConsumptionScore__days_inweek_eaten_oil',
        'foodConsumptionScore__days_inweek_eaten_sugar',
        'foodConsumptionScore__days_inweek_eaten_bread',
        'foodConsumptionScore__food_cerel_source',
        
        'householdStrategyFood__number_days_nothave_enough_food_less_expensive',
        'householdStrategyFood__number_days_nothave_enough_food_barrow',
        'householdStrategyFood__number_days_nothave_enough_food_limit_portion',
        'householdStrategyFood__number_days_nothave_enough_food_restrict_sonsumption',
        'householdStrategyFood__number_days_nothave_enough_food_reduce_meals',
        'householdStrategyFood__household_stocks_cereals',
        'householdStrategyFood__market_place',
        'householdStrategyFood__marketplace_distance',

        'communityAvailability__community_avalibility',
        'communityAvailability__community_center_photo', 
        'communityAvailability__community_org_female', 
        'communityAvailability__community_org_male', 
        'communityAvailability__Household_member_participate',  
        'communityAvailability__Household_member_participate_yes', 

        'livelihood__Household_main_source_income',
        'livelihood__women_engagement_income',
        'livelihood__average_Household_monthly_income',
        'livelihood__improve_livelihoods',
        'livelihood__improve_livelihoods_other',
        'livelihood__debt',
        'livelihood__repaying_load_yes',

        'durableSolution__future_families_preference',
        'durableSolution__local_integration_details',
        'durableSolution__local_integration_other',
        'durableSolution__do_you_have_land',
        'durableSolution__do_you_have_land_yes',

        'skillIdea__members_have_skills',
        'skillIdea__type_skills',
        'skillIdea__type_skills_other',
        'skillIdea__skills_want_learn',

        'resettlement__relocate_another_place_by_government',
        'resettlement__reason_notwantto_relocate',
        'resettlement__relocate_minimum_condition',

        'recentAssistance__receive_assistance',
        'recentAssistance__type_assistance',
        'recentAssistance__assistance_provided_by',

    ];
    }
}