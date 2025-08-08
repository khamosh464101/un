<?php
namespace Modules\DataManagement\Services;

use Illuminate\Support\Facades\Http;
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
use Modules\DataManagement\Models\SubmissionStatus;
use Illuminate\Support\Str;

use DB;

class UpdateSubmissionParser
{
     protected KoboService $koboService;

    public function __construct(KoboService $koboService)
    {
        $this->koboService = $koboService;
    }


    public function parseAndReturn(array $submission, $id)
    {
        try {
           $savedSubmission = DB::transaction(function () use ($submission, $id) {
             $sub = $this->updateSubmission($submission, $id);
            $sourceInformation = $this->updateSourceInformation($submission, $sub);
           $familyInformation = $this->createFamilyInformation($submission, $sub);
           
           if ($familyInformation->hof_or_interviewee === "yes") {
            $this->createHeadFamily($submission, $sub);
           } else {
            $this->createInterviewwee($submission, $sub);
           }

           $this->createComposition($submission, $sub);
           if ($sub->status === "idp" ) {
            $this->createIdp($submission, $sub);
           }else if($sub->status === "returnee" ) {
            $this->createReturnee($submission, $sub);
            
           }

           $this->updateExtremelyVulnerableMember($submission, $sub);
           $this->updateAccessCivilDocumentMale($submission, $sub);
           $this->updateAccessCivilDocumentFemale($submission, $sub);
           $this->updateHouseLandOwnership($submission, $sub);
           $this->updateHouseCondition($submission, $sub);
           $this->updateAccessBasicService($submission, $sub);
           $this->updateFoodConsumptionScore($submission, $sub);
           $this->updateHouseholdStrategyFood($submission, $sub);
           $this->updateCommunityAvailability($submission, $sub);
           $this->updateLivelihood($submission, $sub);
           $this->updateDurableSolution($submission, $sub);
           $this->updateSkillIdea($submission, $sub);
           $this->updateResettlement($submission, $sub);
           $this->updateRecentAssistance($submission, $sub);
           $this->updateInfrasttructureService($submission, $sub);
          $this->updatePhotoSection($submission, $sub);

   

            
            });
        return [
            'success' => true,
            'message' => 'Submission saved.',
            'data' => $savedSubmission
        ];
        } catch (\Exception $e) {
             logger()->error("Error occured: ". $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error occurred.',
                'error' => $e->getMessage()
            ];
        }

    }

    function updateSubmission($submission, $id) {
        // $fillable = (new Submission)->getIgnoreIdFillable();
        // unset($fillable['_submission_time']);
        $fillable = ['consent', 'status'];
        //  'id',
        // '_id', 
        // '_uuid', 
        // 'start',
        // 'end',
        // '__version__',
        // '_submission_time',
        // 'consent',
        // 'status',
        // 'today', 
        // 'dm_form_id',
        // 'submission_status_id'
        
        $filteredData = array_intersect_key($submission, array_flip($fillable));
        $sub = Submission::find($id);
        $sub->update($filteredData);
    
        return $sub;
    }

    function updateSourceInformation($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new SourceInformation)->getIgnoreIdFillable())
        );
        $si = SourceInformation::find($submission['source_information__id']);
        $si->update($filteredData);

        return $si;
    } 

    function createFamilyInformation($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new FamilyInformation)->getIgnoreIdFillable())
        );
        $fi = SourceInformation::find($submission['family_information__id']);
        $fi->update($filteredData);
        return $fi;
    } 

    function createHeadFamily($submission, $sub) {
         $filteredData = array_intersect_key(
            $submission,
            array_flip((new HeadFamily)->getIgnoreIdFillable())
        );
        return $sub->headFamily()->update($filteredData);
    }

    function createInterviewwee($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Interviewwee)->getIgnoreIdFillable())
        );
        return $sub->interviewwee()->update($filteredData);
    }

     function createComposition($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Composition)->getIgnoreIdFillable())
        );
        return $sub->composition()->update($filteredData);
    }

    function createIdp($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Idp)->getIgnoreIdFillable())
        );
        return $sub->idp()->update($filteredData);
    }


    function createReturnee($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Returnee)->getIgnoreIdFillable())
        );
         $sub->returnee()->update($filteredData);

        return $sub;
    }

    function updateExtremelyVulnerableMember($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new ExtremelyVulnerableMember)->getIgnoreIdFillable())
        );
        return $sub->extremelyVulnerableMember()->update($filteredData);
    }

    function updateAccessCivilDocumentMale($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new AccessCivilDocumentMale)->getIgnoreIdFillable())
        );
        return $sub->accessCivilDocumentMale()->update($filteredData);
    }

   function updateAccessCivilDocumentFemale($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new AccessCivilDocumentFemale)->getIgnoreIdFillable())
        );
        return $sub->accessCivilDocumentFemale()->update($filteredData);
    }

    function updateHouseLandOwnership($submission, $sub) {
        
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new HouseLandOwnership)->getIgnoreIdFillable())
        );
        $sub->houseLandOwnership()->update($filteredData);
        return $sub;
    }

    function updateHouseCondition($submission, $sub) {
        $submission['issues_current_house'] = '"' . implode(' ', $submission['issues_current_house']) . '"';
        
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new HouseCondition)->getIgnoreIdFillable())
        );
         $sub->houseCondition()->update($filteredData);
         
         if (isset($submission['house_problems_area_photos'])) {
            foreach ($submission['house_problems_area_photos'] as $key => $value) {
                if (isset($value['id']) && $value['id']) {
                    HouseProblemAreaPhoto::find($value['id'])->update($value);
                } else {
                    HouseProblemAreaPhoto::create([
                        'current_house_problem_title' => $value["current_house_problem_title"],
                        'current_house_problem_photo' => $value["current_house_problem_photo"],
                        'dm_house_condition_id' => $sub->houseCondition->id
                    ]);
                }

                
            }
        }

        return $sub;
    }

    function updateAccessBasicService($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new AccessBasicService)->getIgnoreIdFillable())
        );
        return $sub->accessBasicService()->update($filteredData);
    }

    function updateFoodConsumptionScore($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new FoodConsumptionScore)->getIgnoreIdFillable())
        );
        return $sub->foodConsumptionScore()->update($filteredData);
    }

    function updateHouseholdStrategyFood($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new HouseholdStrategyFood)->getIgnoreIdFillable())
        );
        return $sub->householdStrategyFood()->update($filteredData);
    }

    function updateCommunityAvailability($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new CommunityAvailability)->getIgnoreIdFillable())
        );
        return $sub->communityAvailability()->update($filteredData);
    }

    function updateLivelihood($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Livelihood)->getIgnoreIdFillable())
        );
        return $sub->livelihood()->update($filteredData);
    }


    function updateDurableSolution($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new DurableSolution)->getIgnoreIdFillable())
        );
        return $sub->durableSolution()->update($filteredData);
    }

    function updateSkillIdea($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new SkillIdea)->getIgnoreIdFillable())
        );
        return $sub->skillIdea()->update($filteredData);
    }

    function updateResettlement($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Resettlement)->getIgnoreIdFillable())
        );
        return $sub->resettlement()->update($filteredData);
    }

    function updateRecentAssistance($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new RecentAssistance)->getIgnoreIdFillable())
        );
        return $sub->recentAssistance()->update($filteredData);
    }

    function updateInfrasttructureService($submission, $sub) {
        $filteredData = [];
        $filteredData['infrastructure_services_settlement'] = '"' . implode(' ', $submission['infrastructure_services_settlement']) . '"';
        return $sub->infrasttructureService()->update($filteredData);
    }

    function updatePhotoSection($submission, $sub) {
        
        unset($submission['altitude']);
        unset($submission['accuracy']);
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new PhotoSection)->getIgnoreIdFillable())
        );
       
        return $sub->photoSection()->update($filteredData);
    }

    function convertToMySQLDateTime($isoDatetime) {
        try {
            $dt = new \DateTime($isoDatetime);
            return $dt->format('Y-m-d H:i:s'); 
        } catch (Exception $e) {
            return null;
        }
    }

}
