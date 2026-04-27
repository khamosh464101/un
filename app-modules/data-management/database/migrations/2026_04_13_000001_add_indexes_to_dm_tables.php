<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── submission_id indexes on all child tables ─────────────────────────
        $tables = [
            'dm_source_information',
            'dm_family_information',
            'dm_head_families',
            'dm_interviewwees',
            'dm_compositions',
            'dm_idps',
            'dm_returnees',
            'dm_extremely_vulnerable_members',
            'dm_access_civil_document_males',
            'dm_access_civil_document_females',
            'dm_house_land_ownerships',
            'dm_house_conditions',
            'dm_access_basic_services',
            'dm_food_consumption_scores',
            'dm_household_strategy_food',
            'dm_community_availabilties',
            'dm_livelihoods',
            'dm_durable_solutions',
            'dm_skill_ideas',
            'dm_resettlements',
            'dm_recent_assistances',
            'dm_infrasttructure_services',
            'dm_photo_sections',
            'dm_submission_extra_attributes',
        ];

        foreach ($tables as $table) {
            if (!$this->indexExists($table, 'submission_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->index('submission_id');
                });
            }
        }

        // ── dm_submissions ────────────────────────────────────────────────────
        Schema::table('dm_submissions', function (Blueprint $table) {
            if (!$this->indexExists('dm_submissions', '_id')) {
                $table->index('_id');
            }
            if (!$this->indexExists('dm_submissions', 'dm_form_id')) {
                $table->index('dm_form_id');
            }
            if (!$this->indexExists('dm_submissions', 'submission_status_id')) {
                $table->index('submission_status_id');
            }
            if (!$this->indexExists('dm_submissions', 'today')) {
                $table->index('today');
            }
        });

        // ── project_submission pivot ──────────────────────────────────────────
        Schema::table('project_submission', function (Blueprint $table) {
            if (!$this->indexExists('project_submission', 'project_id')) {
                $table->index('project_id');
            }
            if (!$this->indexExists('project_submission', 'submission_id')) {
                $table->index('submission_id');
            }
        });

        // ── dm_submission_extra_attributes composite ──────────────────────────
        Schema::table('dm_submission_extra_attributes', function (Blueprint $table) {
            if (!$this->indexExists('dm_submission_extra_attributes', 'dm_sea_submission_attr_index')) {
                $table->index(['submission_id', 'attribute_name'], 'dm_sea_submission_attr_index');
            }
        });

        // ── dm_parcels ────────────────────────────────────────────────────────
        Schema::table('dm_parcels', function (Blueprint $table) {
            if (!$this->indexExists('dm_parcels', 'province')) {
                $table->index('province');
            }
            if (!$this->indexExists('dm_parcels', 'district')) {
                $table->index('district');
            }
            if (!$this->indexExists('dm_parcels', 'land_use_type')) {
                $table->index('land_use_type');
            }
        });
    }

    private function indexExists(string $table, string $column): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Column_name = ?", [$column]);
        return count($indexes) > 0;
    }

    public function down(): void
    {
        $tables = [
            'dm_source_information',
            'dm_family_information',
            'dm_head_families',
            'dm_interviewwees',
            'dm_compositions',
            'dm_idps',
            'dm_returnees',
            'dm_extremely_vulnerable_members',
            'dm_access_civil_document_males',
            'dm_access_civil_document_females',
            'dm_house_land_ownerships',
            'dm_house_conditions',
            'dm_access_basic_services',
            'dm_food_consumption_scores',
            'dm_household_strategy_food',
            'dm_community_availabilties',
            'dm_livelihoods',
            'dm_durable_solutions',
            'dm_skill_ideas',
            'dm_resettlements',
            'dm_recent_assistances',
            'dm_infrasttructure_services',
            'dm_photo_sections',
            'dm_submission_extra_attributes',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropIndex("{$table}_submission_id_index");
            });
        }

        Schema::table('dm_submissions', function (Blueprint $table) {
            $table->dropIndex('dm_submissions__id_index');
            $table->dropIndex('dm_submissions_dm_form_id_index');
            $table->dropIndex('dm_submissions_submission_status_id_index');
            $table->dropIndex('dm_submissions_today_index');
        });

        Schema::table('project_submission', function (Blueprint $table) {
            $table->dropIndex('project_submission_project_id_index');
            $table->dropIndex('project_submission_submission_id_index');
        });

        Schema::table('dm_submission_extra_attributes', function (Blueprint $table) {
            $table->dropIndex('dm_submission_extra_attributes_submission_id_attribute_name_index');
        });

        Schema::table('dm_parcels', function (Blueprint $table) {
            $table->dropIndex('dm_parcels_province_index');
            $table->dropIndex('dm_parcels_district_index');
            $table->dropIndex('dm_parcels_land_use_type_index');
        });
    }
};
