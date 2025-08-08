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

class CreateSubmissionParser
{
     protected KoboService $koboService;

    public function __construct(KoboService $koboService)
    {
        $this->koboService = $koboService;
    }


    public function parseAndReturn(array $submission)
    {
       

        try {
           $savedSubmission = DB::transaction(function () use ($submission) {
             $sub = $this->createSubmission($submission);
            $sourceInformation = $this->createSourceInformation($submission, $sub);
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

           $this->createExtremelyVulnerableMember($submission, $sub);
           $this->createAccessCivilDocumentMale($submission, $sub);
           $this->createAccessCivilDocumentFemale($submission, $sub);
           $this->createHouseLandOwnership($submission, $sub);
           $this->createHouseCondition($submission, $sub);
           $this->createAccessBasicService($submission, $sub);
           $this->createFoodConsumptionScore($submission, $sub);
           $this->createHouseholdStrategyFood($submission, $sub);
           $this->createCommunityAvailability($submission, $sub);
           $this->createLivelihood($submission, $sub);
           $this->createDurableSolution($submission, $sub);
           $this->createSkillIdea($submission, $sub);
           $this->createResettlement($submission, $sub);
           $this->createRecentAssistance($submission, $sub);
           $this->createInfrasttructureService($submission, $sub);
          $this->createPhotoSection($submission, $sub);

   

            
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

    function createSubmission($submission) {
        $fillable = (new Submission)->getIgnoreIdFillable();
        $filteredData = array_intersect_key($submission, array_flip($fillable));
    
 
        $defaultStatus = SubmissionStatus::where('is_default', true)->first();
        $sub = Submission::create(
            array_merge(
                [
                    'dm_form_id' => 1, 
                    'today' => \Carbon\Carbon::today()->toDateString(), 
                    'submission_status_id' => $defaultStatus ? $defaultStatus->id : 1
                ], 
                $filteredData
            )
        );

        $sub->projects()->attach($submission['project_id']);
    
        return $sub;
    }

    function createSourceInformation($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new SourceInformation)->getIgnoreIdFillable())
        );
        $si = SourceInformation::create(
            array_merge(['submission_id' => $sub->id], $filteredData)
        );
        return $si;
    } 

    function createFamilyInformation($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new FamilyInformation)->getIgnoreIdFillable())
        );
        $fi = FamilyInformation::create(
            array_merge(['submission_id' => $sub->id], $filteredData)
        );
        return $fi;
    } 

    function createHeadFamily($submission, $sub) {
         $filteredData = array_intersect_key(
            $submission,
            array_flip((new HeadFamily)->getIgnoreIdFillable())
        );
        return $sub->headFamily()->create($filteredData);
    }

    function createInterviewwee($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Interviewwee)->getIgnoreIdFillable())
        );
        return $sub->interviewwee()->create($filteredData);
    }

     function createComposition($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Composition)->getIgnoreIdFillable())
        );
        return $sub->composition()->create($filteredData);
    }

    function createIdp($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Idp)->getIgnoreIdFillable())
        );
        return $sub->idp()->create($filteredData);
    }


    function createReturnee($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Returnee)->getIgnoreIdFillable())
        );
         $sub->returnee()->create($filteredData);
         if (isset($submission['type_return_document_photos'])) {
            foreach ($submission['type_return_document_photos'] as $key => $value) {
                TypeReturnDocumentPhoto::create([
                    'type_return_document_photo' => $value,
                    'dm_returnee_id' => $sub->returnee->id
                ]);
            }
         }

        return $sub;
    }

    function createExtremelyVulnerableMember($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new ExtremelyVulnerableMember)->getIgnoreIdFillable())
        );
        return $sub->extremelyVulnerableMember()->create($filteredData);
    }

    function createAccessCivilDocumentMale($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new AccessCivilDocumentMale)->getIgnoreIdFillable())
        );
        return $sub->accessCivilDocumentMale()->create($filteredData);
    }

   function createAccessCivilDocumentFemale($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new AccessCivilDocumentFemale)->getIgnoreIdFillable())
        );
        return $sub->accessCivilDocumentFemale()->create($filteredData);
    }

    function createHouseLandOwnership($submission, $sub) {
        
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new HouseLandOwnership)->getIgnoreIdFillable())
        );
        $sub->houseLandOwnership()->create($filteredData);
        if (isset($submission['house_document_photos'])) {
            foreach ($submission['house_document_photos'] as $key => $value) {
                LandOwnershipDocument::create([
                    'house_document_photo' => $value,
                    'dm_house_land_ownership_id' => $sub->houseLandOwnership->id
                ]);
            }
        }
        return $sub;
    }

    function createHouseCondition($submission, $sub) {
        
        $submission['issues_current_house'] = implode(' ', $submission['issues_current_house']);
         
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new HouseCondition)->getIgnoreIdFillable())
        );

        
         $sub->houseCondition()->create($filteredData);
         if (isset($submission['house_problems_area_photos'])) {
            foreach ($submission['house_problems_area_photos'] as $key => $value) {
                HouseProblemAreaPhoto::create([
                    'current_house_problem_title' => $value["current_house_problem_title"],
                    'current_house_problem_photo' => $value["current_house_problem_photo"],
                    'dm_house_condition_id' => $sub->houseCondition->id
                ]);
            }
        }

        return $sub;
    }

    function createAccessBasicService($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new AccessBasicService)->getIgnoreIdFillable())
        );
        return $sub->accessBasicService()->create($filteredData);
    }

    function createFoodConsumptionScore($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new FoodConsumptionScore)->getIgnoreIdFillable())
        );
        return $sub->foodConsumptionScore()->create($filteredData);
    }

    function createHouseholdStrategyFood($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new HouseholdStrategyFood)->getIgnoreIdFillable())
        );
        return $sub->householdStrategyFood()->create($filteredData);
    }

    function createCommunityAvailability($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new CommunityAvailability)->getIgnoreIdFillable())
        );
        return $sub->communityAvailability()->create($filteredData);
    }

    function createLivelihood($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Livelihood)->getIgnoreIdFillable())
        );
        return $sub->livelihood()->create($filteredData);
    }


    function createDurableSolution($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new DurableSolution)->getIgnoreIdFillable())
        );
        return $sub->durableSolution()->create($filteredData);
    }

    function createSkillIdea($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new SkillIdea)->getIgnoreIdFillable())
        );
        return $sub->skillIdea()->create($filteredData);
    }

    function createResettlement($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new Resettlement)->getIgnoreIdFillable())
        );
        return $sub->resettlement()->create($filteredData);
    }

    function createRecentAssistance($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new RecentAssistance)->getIgnoreIdFillable())
        );
        return $sub->recentAssistance()->create($filteredData);
    }

    function createInfrasttructureService($submission, $sub) {
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new InfrasttructureService)->getIgnoreIdFillable())
        );
        $filteredData['infrastructure_services_settlement'] = implode(' ', $submission['infrastructure_services_settlement']);

        return $sub->infrasttructureService()->create($filteredData);
    }

    function createPhotoSection($submission, $sub) {
        
        unset($submission['altitude']);
        unset($submission['accuracy']);
        $filteredData = array_intersect_key(
            $submission,
            array_flip((new PhotoSection)->getIgnoreIdFillable())
        );
       
        return $sub->photoSection()->create($filteredData);
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
