<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DataManagement\Models\Submission;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\ProjectStatus;
use Modules\Projects\Models\ActivityStatus;
use Modules\Projects\Models\TicketStatus;
use Modules\Projects\Models\Staff;
use Modules\Projects\Models\Activity;
use Modules\Projects\Models\Ticket;
use Modules\Projects\Models\Donor;
use Modules\Projects\Models\Partner;
use Modules\Projects\Models\Subproject;
use Modules\Projects\Models\SubprojectType;
use Modules\DataManagement\Models\Form;
use DB;

class PowerbiController extends Controller
{
    public function getBIData() {
        $form = Form::first();
        $dataObject = json_decode($form->raw_schema);
        $choices = $dataObject->asset->content->choices;
        $choicesMap = collect($choices)->pluck('label.0', 'name');
        $submissions = Submission::all();

        $submissions = Submission::with([
            'dstatus:id,title,color',
            'sourceInformation:id,submission_id,survey_province,kbl_guzar_number,province_code,city_code,district_code,block_number,house_number',
            'familyInformation:id,submission_id,hoh_disable,province_origin',
            'headFamily:id,submission_id,hoh_sex',
            'interviewwee:id,submission_id,inter_sex',
            'returnee:id,submission_id,entry_borders,reason_return',
            'extremelyVulnerableMember:id,submission_id,disable_member,conditional_women,conditional_women_pregnant,conditional_women_breastfeeding_mother,conditional_women_widow',
            'houseLandOwnership:id,submission_id,house_owner',
            'accessBasicService:id,submission_id,drinkingwater_main_source,type_water_source,water_source_distance,water_source_route_safe,water_collect_person,water_quality,type_toilet_facilities,access_education,access_school,type_school,nearest_school,access_school_university,access_school_madrasa,Household_members_attend_school_present,Household_members_attend_university_present,litrate_Household_member,access_health_services,health_facilities_type,health_service_distance,health_facility_have_female_staff,health_challanges,type_access_road,how_access_electricity',
            'livelihood:id,submission_id,Household_main_source_income',
            'photoSection:id,submission_id,latitude,longitude',
        ])->limit(500)->get();



        $submissionColumnsToProcess = [
            'submission' => ['status'], // Changed from 'submissions' to match your processing code
            'sourceInformation' => ['survey_province', 'kbl_guzar_number', 'province_code', 'city_code', 'district_code', 'block_number', 'house_number'],
            'familyInformation' => ['hoh_disable', 'province_origin'],
            'headFamily' => ['hoh_sex'],
            'interviewwee' => ['inter_sex'],
            'returnee' => ['entry_borders', 'reason_return'],
            'extremelyVulnerableMember' => ['disable_member','conditional_women','conditional_women_pregnant','conditional_women_breastfeeding_mother','conditional_women_widow'],
            'houseLandOwnership' => ['house_owner'],
            'accessBasicService' => ['drinkingwater_main_source', 'type_water_source', 'water_source_distance', 'water_source_route_safe', 'water_collect_person', 'water_quality','type_toilet_facilities','access_education','access_school','type_school','nearest_school','access_school_university','access_school_madrasa', 'Household_members_attend_school_present', 'litrate_Household_member','access_health_services','health_facilities_type', 'health_service_distance','health_facility_have_female_staff', 'health_challanges','type_access_road','how_access_electricity'],
            'livelihood' => ['Household_main_source_income'],
            'photoSection' => ['latitude', 'longitude'],
        ];

        $submissions->each(function ($submission) use ($choicesMap, $submissionColumnsToProcess) {
            // Process direct submission attributes
            if (isset($submissionColumnsToProcess['submission'])) {
                foreach ($submissionColumnsToProcess['submission'] as $column) {
                    if (isset($submission->$column)) {
                        $originalValue = $submission->$column;
                        $submission->$column = $choicesMap->get($originalValue, $originalValue);
                        
                        // Debug output
                        if ($originalValue !== $submission->$column) {
                            \Log::debug("Replaced $column: $originalValue => {$submission->$column}");
                        }
                    }
                }
            }
        
            // Process one-to-one relationships
            foreach ($submissionColumnsToProcess as $relation => $columns) {
                if ($relation === 'submission') continue;
                
                if ($submission->$relation) {
                    foreach ($columns as $column) {
                        if (isset($submission->$relation->$column)) {
                            $originalValue = $submission->$relation->$column;
                            $submission->$relation->$column = $choicesMap->get($originalValue, $originalValue);
                            
                            // Debug output
                            if ($originalValue !== $submission->$relation->$column) {
                                \Log::debug("Replaced $relation.$column: $originalValue => {$submission->$relation->$column}");
                            }
                        }
                    }
                }
            }
        });
        $submissions = $submissions->map(function ($submission) {
            return [
                'submission' => $submission->only(['id', 'today', 'status']),
                'sourceInformation' => $submission->sourceInformation,
                'familyInformation' => $submission->familyInformation,
                'headFamily' => $submission->headFamily,
                'interviewwee' => $submission->interviewwee,
                'returnee' => $submission->returnee,
                'extremelyVulnerableMember' => $submission->extremelyVulnerableMember,
                'houseLandOwnership' => $submission->houseLandOwnership,
                'accessBasicService' => $submission->accessBasicService,
                'livelihood' => $submission->livelihood,
                'photoSection' => $submission->photoSection,

                // Include all other submission relationships
            ];
        });
        return response()->json([
            'project_statuses' => ProjectStatus::select('id', 'title')->get(),
            'donors' => Donor::select('id', 'name')->get(),
            'projects' => Project::select('id', 'title', 'estimated_budget', 'spent_budget', 'donor_id', 'project_status_id')->get(),
            'partners' => Partner::select('id', 'business_name')->get(),
            'subproject_types' => SubprojectType::select('id', 'title')->get(),
            'subprojects' => Subproject::select('id', 'title', 'budget', 'partner_id', 'subproject_type_id', 'project_id')->get(),
            'activity_statuses' => ActivityStatus::select('id', 'title')->get(),
            'ticket_statuses' => TicketStatus::select('id', 'title')->get(),
            'activities' => Activity::select('id', 'title', 'activity_status_id', 'project_id')->get(),
            'tickets' => Ticket::all(),
            'staffs' => Staff::select('id', 'name')->get(),
            'submissions' => $submissions,
            'project_submission' => DB::table('project_submission')->get()
        ]);

        
    }
}
