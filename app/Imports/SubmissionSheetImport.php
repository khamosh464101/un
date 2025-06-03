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
use Modules\DataManagement\Models\Livelihood;
use Modules\DataManagement\Models\DurableSolution;
use Modules\DataManagement\Models\SkillIdea;
use Modules\DataManagement\Models\Resettlement;
use Modules\DataManagement\Models\RecentAssistance;
use Modules\DataManagement\Models\InfrasttructureService;
use Modules\DataManagement\Models\PhotoSection;
use Modules\DataManagement\Models\Form;
use Carbon\Carbon;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date;
// ✅ Disable header formatting (keep labels exactly as in Excel)
HeadingRowFormatter::default('none');

class SubmissionSheetImport implements ToModel, WithHeadingRow, WithChunkReading, WithLimit
{
    private const DATES = [
        'today', 
        'date_return_home_country', 
        'type_return_document_date', 
        'house_document_date'
    ];
    private const PHOTOS = ['hoh_nic_photo', 'inter_nic_photo', 'inter_nic_photo_owner'];
    public function model(array $row)
    {
        // try {
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

            $household_strategy_food = (new HouseholdStrategyFlood)->getIgnoreIdFillable();
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

            $recent_assistance = (new ResentAssistance)->getIgnoreIdFillable();
            $recent_assistance_data = [];

            // $recent_assistance = (new ResentAssistance)->getIgnoreIdFillable();
            // $recent_assistance_data = [];

            // $recent_assistance = (new ResentAssistance)->getIgnoreIdFillable();
            // $recent_assistance_data = [];

            // $recent_assistance = (new ResentAssistance)->getIgnoreIdFillable();
            // $recent_assistance_data = [];

            
            
            
            
                
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

                if (in_array($surveyItem->name, $house_condition, true)) {
                    $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
                
                    if ($result !== 12345) { 
                        $house_condition_data[$surveyItem->name] = $result;
                    }
                }

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

         
                
            }

            $submission_data['dm_form_id'] = $form->id;
            
            $submission = new Submission($submission_data);
            $submission->save();

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
            

            
        // } catch (\Exception $e) {
        //     logger()->error("Error importing row: " . $e->getMessage());
        // }
    }

    public function chunkSize(): int
    {
        return 1;
    }

    public function limit(): int
    {
        return 5;
    }

    private function getDate($date): Carbon
    {
        return Carbon::instance(Date::excelToDateTimeObject($date));
    }

    private function checkChoice($choices, $labelValue) {
        foreach ($choices as $choice) {
            if ($choice->label[0] === $labelValue) {
                return $choice->name;
            }
        }
        return false;
    }

    private function getSingleValue($surveyItem, $row, $choices, $fieldName) {
        if (isset($surveyItem->label) && isset($row[$surveyItem->label[0]])) {
            $labelValue = $row[$surveyItem->label[0]];
            $result = $this->checkChoice($choices, $labelValue);
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
}
