<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Performance indexes for columns frequently used in whereHas filters.
 */
return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'dm_source_information' => ['province_code', 'city_code', 'district_code', 'district_name', 'kbl_guzar_number', 'block_number', 'house_number', 'survey_province', 'surveyors_name', 'village_name'],
            'dm_submissions' => ['status', 'user_id'],
            'dm_family_information' => ['hoh_disable', 'province_origin', 'district_origin'],
            'dm_head_families' => ['hoh_name', 'hoh_sex'],
            'dm_interviewwees' => ['inter_name'],
            'dm_house_land_ownerships' => ['house_owner'],
            'dm_access_basic_services' => ['drinkingwater_main_source', 'access_education', 'access_health_services', 'type_access_road', 'how_access_electricity'],
            'dm_livelihoods' => ['Household_main_source_income'],
            'tickets' => ['owner_id', 'responsible_id', 'activity_id', 'ticket_status_id', 'ticket_priority_id'],
            'activities' => ['project_id', 'activity_status_id'],
            'dm_bulk_download_items' => ['status'],
        ];

        foreach ($indexes as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                foreach ($columns as $col) {
                    if (!$this->indexExists($table, $col)) {
                        $t->index($col);
                    }
                }
            });
        }
    }

    private function indexExists(string $table, string $column): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Column_name = ?", [$column]);
        return count($indexes) > 0;
    }

    public function down(): void
    {
        $indexes = [
            'dm_source_information' => ['province_code', 'city_code', 'district_code', 'district_name', 'kbl_guzar_number', 'block_number', 'house_number', 'survey_province', 'surveyors_name', 'village_name'],
            'dm_submissions' => ['status', 'user_id'],
            'dm_family_information' => ['hoh_disable', 'province_origin', 'district_origin'],
            'dm_head_families' => ['hoh_name', 'hoh_sex'],
            'dm_interviewwees' => ['inter_name'],
            'dm_house_land_ownerships' => ['house_owner'],
            'dm_access_basic_services' => ['drinkingwater_main_source', 'access_education', 'access_health_services', 'type_access_road', 'how_access_electricity'],
            'dm_livelihoods' => ['Household_main_source_income'],
            'tickets' => ['owner_id', 'responsible_id', 'activity_id', 'ticket_status_id', 'ticket_priority_id'],
            'activities' => ['project_id', 'activity_status_id'],
            'dm_bulk_download_items' => ['status'],
        ];

        foreach ($indexes as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                foreach ($columns as $col) {
                    $t->dropIndex("{$table}_{$col}_index");
                }
            });
        }
    }
};
