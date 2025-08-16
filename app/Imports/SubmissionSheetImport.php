<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Modules\DataManagement\Models\Submission;
use Modules\DataManagement\Models\SourceInformation;
use Modules\DataManagement\Models\FamilyInformation;
use Modules\DataManagement\Models\HeadFamily;
use Modules\DataManagement\Models\Interviewwee;
use Modules\DataManagement\Models\Composition;
use Modules\DataManagement\Models\Idp;
use Modules\DataManagement\Models\Returnee;
use Modules\DataManagement\Models\TypeReturnDocumentPhoto;
use Modules\DataManagement\Models\ExtremelyVulnerableMember;
use Modules\DataManagement\Models\AccessCivilDocumentMale;
use Modules\DataManagement\Models\AccessCivilDocumentFemale;
use Modules\DataManagement\Models\HouseLandOwnership;
use Modules\DataManagement\Models\LandOwnershipDocument;
use Modules\DataManagement\Models\HouseCondition;
use Modules\DataManagement\Models\HouseProblemAreaPhoto;
use Modules\DataManagement\Models\AccessBasicService;
use Modules\DataManagement\Models\FoodConsumptionScore;
use Modules\DataManagement\Models\HouseholdStrategyFood;
use Modules\DataManagement\Models\CommunityAvailability;
use Modules\DataManagement\Models\Livelihood;
use Modules\DataManagement\Models\DurableSolution;
use Modules\DataManagement\Models\SkillIdea;
use Modules\DataManagement\Models\Resettlement;
use Modules\DataManagement\Models\RecentAssistance;
use Modules\DataManagement\Models\InfrasttructureService;
use Modules\DataManagement\Models\PhotoSection;
use Modules\DataManagement\Models\Form;
use Modules\DataManagement\Models\SubmissionStatus;
use Modules\Projects\Models\Project;
use Carbon\Carbon;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;
use Modules\DataManagement\Services\KoboService;
use Modules\DataManagement\Services\KoboSubmissionParser;
use Maatwebsite\Excel\Concerns\WithStartRow;

// ✅ Disable header formatting (keep labels exactly as in Excel)
HeadingRowFormatter::default('none');

class SubmissionSheetImport implements ToModel, WithStartRow, WithHeadingRow, WithChunkReading, WithLimit
{
    protected $startRow;
    protected $limit;
    protected $chunkSize;
    protected $projectId;

    public function __construct($startRow, $limit, $projectId)
    {
        $this->startRow = $startRow;
        $this->limit = $limit;
        $this->chunkSize = 5; // Default value
        $this->projectId = $projectId;
    }

    // Add this setter method
    public function setChunkSize(int $chunkSize): self
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }

    // Keep this but now it returns the dynamic value
    public function chunkSize(): int
    {
        return $this->chunkSize;
    }
    
    private const DATES = [
        'today', 
        'start',
        'end',
        'date_return_home_country', 
        'type_return_document_date', 
        'house_document_date'
    ];
    private const PHOTOS = [
        'hoh_nic_photo', 
        'inter_nic_photo', 
        'inter_nic_photo_owner',
        'water_point_photo',
        'access_sanitation_photo',
        'access_education_photo',
        'access_health_photo',
        'access_road_photo'
    ];

     private const FEMALE_ACCESS_CIVIL_DOCUMENTS_FIELDS = [
        'access_civil_documentation_female_tazkira',
        'access_civil_documentation_female_birthcertificate',
        'access_civil_documentation_female_marriagecertificate',
        'access_civil_documentation_female_departationcard',
        'access_civil_documentation_female_drivinglicense',
    ];

    private const OTHERS = [
        'house_adequate_family_size_no_other',
        'members_attend_madrasa_no',
        'members_attend_madrasa_yes_boys',
        'members_attend_madrasa_yes_girls',
        'Household_member_participate_yes',

    ];

    


    
    public function model(array $row)
    {
        \DB::transaction(function () use ($row) {
            logger()->info('Memory usage: ' . (memory_get_usage(true)/1024/1024) . ' MB');
                   logger()->info('test'.$this->startRow.$this->limit);
        if (Submission::where('_id', $row['_id'])->exists()) {
            logger()->info($row['_id']);
            return null; // ❌ Don't import this row
        }logger()->info($row['_id'].'end');
        // try {
            $row['1.7 Block Code Number'] = str_pad($row['1.7 Block Code Number'], 3, "0", STR_PAD_LEFT);
            $row['1.6 Guzar Code Number'] = str_pad($row['1.6 Guzar Code Number'], 3, "0", STR_PAD_LEFT);

            logger()->info("Processing row:", $row);
            $form = Form::first();
            $schema = json_decode($form->raw_schema);
            $survey = $schema->asset->content->survey ?? [];
            $choices = $schema->asset->content->choices ?? [];
            
            $submission = (new Submission)->getIgnoreIdFillable();
            $submission_data = [];

            $source_information = (new SourceInformation)->getIgnoreIdFillable();
            $source_information_data = [];

            $family_information = (new FamilyInformation)->getIgnoreIdFillable();
            $family_information_data = [];

            $head_family = (new HeadFamily)->getIgnoreIdFillable();
            $head_family_data = [];

            $interviewwee = (new Interviewwee)->getIgnoreIdFillable();
            $interviewwee_data = [];

            $composition = (new Composition)->getIgnoreIdFillable();
            $composition_data = [];

            $idp = (new Idp)->getIgnoreIdFillable();
            $idp_data = [];

            $returnee = (new Returnee)->getIgnoreIdFillable();
            $returnee_data = [];

            $extremely_vulnerable_member = (new ExtremelyVulnerableMember)->getIgnoreIdFillable();
            $extremely_vulnerable_member_data = [];

            $access_civil_document_male = (new AccessCivilDocumentMale)->getIgnoreIdFillable();
            $access_civil_document_male_data = [];

            $access_civil_document_female = (new AccessCivilDocumentFemale)->getIgnoreIdFillable();
            $access_civil_document_female_data = [];

            $house_land_ownership = (new HouseLandOwnership)->getIgnoreIdFillable();
            $house_land_ownership_data = [];

            $house_condition = (new HouseCondition)->getIgnoreIdFillable();
            $house_condition_data = [];

            $access_basic_service = (new AccessBasicService)->getIgnoreIdFillable();
            $access_basic_service_data = [];

            $food_consumption_score = (new FoodConsumptionScore)->getIgnoreIdFillable();
            $food_consumption_score_data = [];

            $household_strategy_food = (new HouseholdStrategyFood)->getIgnoreIdFillable();
            $household_strategy_food_data = [];

            $community_availability = (new CommunityAvailability)->getIgnoreIdFillable();
            $community_availability_data = [];

            $livelihood = (new Livelihood)->getIgnoreIdFillable();
            $livelihood_data = [];

            $durable_solution = (new DurableSolution)->getIgnoreIdFillable();
            $durable_solution_data = [];

            $skill_idea = (new SkillIdea)->getIgnoreIdFillable();
            $skill_idea_data = [];

            $resettlement = (new Resettlement)->getIgnoreIdFillable();
            $resettlement_data = [];

            $recent_assistance = (new RecentAssistance)->getIgnoreIdFillable();
            $recent_assistance_data = [];

            $Infrasttructure_service = (new InfrasttructureService)->getIgnoreIdFillable();
            $Infrasttructure_service_data = [];

            $photo_section = (new PhotoSection)->getIgnoreIdFillable();
            $photo_section_data = [];


                
            foreach ($survey as $surveyItem) {
                if (!isset($surveyItem->name)) {
                    continue;
                }
                if (in_array($surveyItem->name, $submission, true)) {
                    
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) {
                        $submission_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name,$source_information, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $source_information_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $family_information, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $family_information_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $head_family, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $head_family_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $interviewwee, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $interviewwee_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $composition, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $composition_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $idp, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $idp_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $returnee, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $returnee_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $extremely_vulnerable_member, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $extremely_vulnerable_member_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $access_civil_document_male, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $access_civil_document_male_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $access_civil_document_female, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $access_civil_document_female_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $house_land_ownership, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $house_land_ownership_data[$surveyItem->name] = $result;
                    }
                }

                // HOUSE CONDITION => DIRECTLY FROM KOBO
                // if (in_array($surveyItem->name, $house_condition, true)) {
                //     $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                //     if ($result !== 12345) { 
                //         $house_condition_data[$surveyItem->name] = $result;
                //     }
                // }

                if (in_array($surveyItem->name, $access_basic_service, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $access_basic_service_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $food_consumption_score, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $food_consumption_score_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $household_strategy_food, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $household_strategy_food_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $community_availability, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $community_availability_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $livelihood, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $livelihood_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $durable_solution, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $durable_solution_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $skill_idea, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $skill_idea_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $resettlement, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $resettlement_data[$surveyItem->name] = $result;
                    }
                }

                if (in_array($surveyItem->name, $recent_assistance, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $recent_assistance_data[$surveyItem->name] = $result;
                    }
                }

                // INFRASTRUCTRUE SERVICE DIRECTLY FROM KOBO
                // PHOTO SECTION DIRECTLY FROM KOBO


         
                
            }

            $defaultStatus = SubmissionStatus::where('is_default', true)->first();
            $submission = Submission::create(array_merge($submission_data, [
                '_id' => $row['_id'],
                'user_id' => auth()->user()->id,
                'dm_form_id' => $form->id,
                'submission_status_id' => $defaultStatus ? $defaultStatus->id : 1
            ]));
            $project = Project::find($this->projectId);
            $project->submissions()->syncWithoutDetaching([$submission->id]);

            $service = new KoboService();
            $parser = new KoboSubmissionParser($service);
            $kobo_submission = $service->getSubmission($submission->_id);
            $kobo_submission = $this->cleanKoboSubmissionKeys($kobo_submission);
            if ($kobo_submission) {
                foreach ($kobo_submission['_attachments'] as $attachment) {
                    if (Str::startsWith($attachment['mimetype'], 'image/')) {
                        $folderName = $submission?->projects?->first()?->id;
                        $service->downloadAttachment($attachment, "kobo-attachments/{$folderName}");
                    }
                }
            } 

            $source_information_data['submission_id'] = $submission->id;
            $sourceInformaton = new SourceInformation($source_information_data);
            $sourceInformaton->save();

            $family_information_data['submission_id'] = $submission->id;
            $familyInformation = new FamilyInformation($family_information_data);
            $familyInformation->save();

            if ($familyInformation->hof_or_interviewee === "yes") {
                $head_family_data['submission_id'] = $submission->id;
                $headFamily = new HeadFamily($head_family_data);
                $headFamily->save();

               } else {
                $interviewwee_data['submission_id'] = $submission->id;
                $interviewwee = new Interviewwee($interviewwee_data);
                $interviewwee->save();
               }
            
            $composition_data['submission_id'] = $submission->id;
            $composition = new Composition($composition_data);
            $composition->save();
            

            if ($submission->status === "idp" ) {
                $idp_data['submission_id'] = $submission->id;
                $idpp = new Idp($idp_data);
                $idpp->save();
               }else if($submission->status === "returnee" ) {
                $returnee_data['submission_id'] = $submission->id;
                $returnee = new Returnee($returnee_data);
                $returnee->save();
                if (isset($kobo_submission['house_document_photos'])) {
                    foreach ($kobo_submission['house_document_photos'] as $key => $value) {
                        TypeReturnDocumentPhoto::create([
                            'type_return_document_photo' => $value["start_survey/returnee/house_document_photos/type_return_document_photo"],
                            'dm_returnee_id' => $returnee->id
                        ]);
                    }
                 }
                
               }
            
            $extremely_vulnerable_member_data['submission_id'] = $submission->id;
            $extremely_vulnerable_member = new ExtremelyVulnerableMember($extremely_vulnerable_member_data);
            $extremely_vulnerable_member->save();

            $access_civil_document_male_data['submission_id'] = $submission->id;
            $access_civil_document_male = new AccessCivilDocumentMale($access_civil_document_male_data);
            $access_civil_document_male->save();

            $access_civil_document_female_data['submission_id'] = $submission->id;
            $access_civil_document_female = new AccessCivilDocumentFemale($access_civil_document_female_data);
            $access_civil_document_female->save();

            $house_land_ownership_data['submission_id'] = $submission->id;
            $house_land_ownership = new HouseLandOwnership($house_land_ownership_data);
            $house_land_ownership->save();
            if (isset($kobo_submission['house_document_photo_repeat'])) {
                foreach ($kobo_submission['house_document_photo_repeat'] as $key => $value) {
                    LandOwnershipDocument::create([
                        'house_document_photo' => $value["start_survey/house_land_ownership/house_document_photo_repeat/house_document_photo"],
                        'dm_house_land_ownership_id' => $house_land_ownership->id
                    ]);
                }
            }

            $parser->createHouseCondition($kobo_submission, $submission);
            // $house_condition_data['submission_id'] = $submission->id;
            // $house_condition = new HouseCondition($house_condition_data);
            // $house_condition->save();
            // if (isset($kobo_submission['house_problems_area_photos'])) {
            //     foreach ($kobo_submission['house_problems_area_photos'] as $key => $value) {
            //         HouseProblemAreaPhoto::create([
            //             'current_house_problem_title' => $value["start_survey/house_condition/house_problems_area_photos/current_house_problem_title"],
            //             'current_house_problem_photo' => $value["start_survey/house_condition/house_problems_area_photos/current_house_problem_photo"],
            //             'dm_house_condition_id' => $house_condition->id
            //         ]);
            //     }
            // }

            $access_basic_service_data['submission_id'] = $submission->id;
            $access_basic_service = new AccessBasicService($access_basic_service_data);
            $access_basic_service->save();

            $food_consumption_score_data['submission_id'] = $submission->id;
            $food_consumption_score = new FoodConsumptionScore($food_consumption_score_data);
            $food_consumption_score->save();

            $household_strategy_food_data['submission_id'] = $submission->id;
            $household_strategy_food = new HouseholdStrategyFood($household_strategy_food_data);
            $household_strategy_food->save();

            $community_availability_data['submission_id'] = $submission->id;
            $community_availability = new CommunityAvailability($community_availability_data);
            $community_availability->save();

            $livelihood_data['submission_id'] = $submission->id;
            $livelihood = new Livelihood($livelihood_data);
            $livelihood->save();

            $durable_solution_data['submission_id'] = $submission->id;
            $durable_solution = new DurableSolution($durable_solution_data);
            $durable_solution->save();

            $skill_idea_data['submission_id'] = $submission->id;
            $skill_idea = new SkillIdea($skill_idea_data);
            $skill_idea->save();
            
            $resettlement_data['submission_id'] = $submission->id;
            $resettlement = new Resettlement($resettlement_data);
            $resettlement->save();
            

            $recent_assistance_data['submission_id'] = $submission->id;
            $recent_assistance = new RecentAssistance($recent_assistance_data);
            $recent_assistance->save();

            $parser->createInfrasttructureService($kobo_submission, $submission);
            $parser->createPhotoSection($kobo_submission, $submission);
            // } catch (\Exception $e) {
            //     logger()->error("Error importing row: " . $e->getMessage());
            // }
        });
 
            
        
    }

    public function startRow(): int
    {
        return $this->startRow ?? 2; // Start from row 31 (skips first 30 rows)
    }


    public function limit(): int
    {
        return $this->limit ?? 10;
    }

    private function getDate($date): Carbon
    {
        return Carbon::instance(Date::excelToDateTimeObject($date));
    }

    private function checkChoice($choices, $labelValue, $surveyItem) {
        if (isset($surveyItem->select_from_list_name)) {
            foreach ($choices as $choice) {
                if ($choice->label[0] === $labelValue && $surveyItem->select_from_list_name === $choice->list_name) {
                    return $choice->name;
                }
            }
        }
        
        return false;
    }

    /// THIS IS THE MAIN ONE
    private function getSingleValue($surveyItem, $row, $choices, $fieldName) {
        if (isset($surveyItem->label)) {
            $label = $surveyItem->label[0];
            if (in_array($fieldName, self::FEMALE_ACCESS_CIVIL_DOCUMENTS_FIELDS)) {
                $label = $label . ' Female';
            }

            if (in_array($fieldName, self::OTHERS)) {
                $label = $label . ' Second';
            }

            if ( isset($row[$label])) {
                $labelValue = $row[$label];
                $result = $this->checkChoice($choices, $labelValue, $surveyItem);
                if ($result) {
                    return $result;
                }

                if (in_array($fieldName, self::PHOTOS)) {
                    return $labelValue; // FOR NOW
                }
                if (in_array($fieldName, self::DATES)) {
                    return $this->getDate($labelValue); 
                }
                return $labelValue;
                

            } 
        }
        
        
        if(isset($row[$surveyItem->name])) {
            $value = $row[$surveyItem->name];
            if (in_array($fieldName, self::DATES)) {
                return $this->getDate($value);

                
            }
            else {
                return $value;
            }
            
        }
        return 12345;
    }

    public function cleanKoboSubmissionKeys(array $submission): array
    {
        $cleaned = [];

        foreach ($submission as $key => $value) {
            // Get the last part after the last slash
            $parts = explode('/', $key);
            $attributeName = end($parts);
            $cleaned[$attributeName] = $value;
        }

        return $cleaned;
    }
}
