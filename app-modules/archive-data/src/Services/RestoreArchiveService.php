<?php
namespace Modules\ArchiveData\Services;
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

use Modules\ArchiveData\Models\Submission as ArchiveSubmission;
use Modules\ArchiveData\Models\SourceInformation as ArchiveSourceInformation;
use Modules\ArchiveData\Models\FamilyInformation as ArchiveFamilyInformation;
use Modules\ArchiveData\Models\HeadFamily as ArchiveHeadFamily;
use Modules\ArchiveData\Models\Interviewwee as ArchiveInterviewwee;
use Modules\ArchiveData\Models\Composition as ArchiveComposition;
use Modules\ArchiveData\Models\Idp as ArchiveIdp;
use Modules\ArchiveData\Models\Returnee as ArchiveReturnee;
use Modules\ArchiveData\Models\TypeReturnDocumentPhoto as ArchiveTypeReturnDocumentPhoto;
use Modules\ArchiveData\Models\ExtremelyVulnerableMember as ArchiveExtremelyVulnerableMember;
use Modules\ArchiveData\Models\AccessCivilDocumentMale as ArchiveAccessCivilDocumentMale;
use Modules\ArchiveData\Models\AccessCivilDocumentFemale as ArchiveAccessCivilDocumentFemale;
use Modules\ArchiveData\Models\HouseLandOwnership as ArchiveHouseLandOwnership;
use Modules\ArchiveData\Models\LandOwnershipDocument as ArchiveLandOwnershipDocument;
use Modules\ArchiveData\Models\HouseCondition as ArchiveHouseCondition;
use Modules\ArchiveData\Models\HouseProblemAreaPhoto as ArchiveHouseProblemAreaPhoto; 
use Modules\ArchiveData\Models\AccessBasicService as ArchiveAccessBasicService;
use Modules\ArchiveData\Models\FoodConsumptionScore as ArchiveFoodConsumptionScore;
use Modules\ArchiveData\Models\HouseholdStrategyFood as ArchiveHouseholdStrategyFood;
use Modules\ArchiveData\Models\CommunityAvailability as ArchiveCommunityAvailability;
use Modules\ArchiveData\Models\Livelihood as ArchiveLivelihood;
use Modules\ArchiveData\Models\DurableSolution as ArchiveDurableSolution;
use Modules\ArchiveData\Models\SkillIdea as ArchiveSkillIdea;
use Modules\ArchiveData\Models\Resettlement as ArchiveResettlement;
use Modules\ArchiveData\Models\RecentAssistance as ArchiveRecentAssistance;
use Modules\ArchiveData\Models\InfrasttructureService as ArchiveInfrasttructureService;
use Modules\ArchiveData\Models\PhotoSection as ArchivePhotoSection;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class RestoreArchiveService
{
    public function restoreSubmission(int $submissionId, int $userId) {
        return DB::transaction(function () use ($submissionId, $userId) {
            $s = Submission::find($submissionId);
            $t = ArchiveSubmission::find($submissionId);
           
            if ($t && !$s) {
                
                $tmp = $t->toArray();
                unset($tmp['archived_by']);
                unset($tmp['archived_at']);
                
                $sub = submission::create($tmp);

                if ($t->sourceInformation && !$sub->sourceInformation){
                    $tmp = $t->sourceInformation->toArray();
                    SourceInformation::create($tmp);
                }

                if ($t->familyInformation && !$sub->familyInformation){
                    $tmp = $t->familyInformation->toArray();
                    FamilyInformation::create($tmp);
                }
                

                if ($t->headFamily && !$sub->headFamily){
                    $t->headFamily->returnRawPhoto = true;
                    $tmp = $t->headFamily->toArray();
                    HeadFamily::create($tmp);
                }

                if ($t->interviewwee && !$sub->interviewwee){
                    $t->interviewwee->returnRawPhoto = true;
                    $tmp = $t->interviewwee->toArray();
                    Interviewwee::create($tmp);
                }

                if ($t->composition && !$sub->composition){
                    $tmp = $t->composition->toArray();
                    Composition::create($tmp);
                }


                if ($t->idp && !$sub->idp){
                    $tmp = $t->idp->toArray();
                    Idp::create($tmp);
                }
                if ($t->returnee && !$sub->returnee) {
                    $tmp = $t->returnee->getAttributes();
                    Returnee::create($tmp);
                
                    foreach ($t->returnee->typeReturnDocumentPhoto as $documentPhoto) {
                        $val = $documentPhoto->getAttributes();
                        TypeReturnDocumentPhoto::create($val);
                    }
                }

                if ($t->extremelyVulnerableMember && !$sub->extremelyVulnerableMember){
                    $tmp = $t->extremelyVulnerableMember->toArray();
                    ExtremelyVulnerableMember::create($tmp);
                }

                if ($t->accessCivilDocumentMale && !$sub->accessCivilDocumentMale){
                    $tmp = $t->accessCivilDocumentMale->toArray();
                    AccessCivilDocumentMale::create($tmp);
                }
                if ($t->accessCivilDocumentFemale && !$sub->accessCivilDocumentFemale){
                    $tmp = $t->accessCivilDocumentFemale->toArray();
                    AccessCivilDocumentFemale::create($tmp);
                }
                if ($t->houseLandOwnership && !$sub->houseLandOwnership){
                    $tmp = $t->houseLandOwnership->getAttributes();
                    HouseLandOwnership::create($tmp);
                    foreach ($t->houseLandOwnership->landOwnershipDocument as $key => $value) {
                        $val = $value->getAttributes();
                        LandOwnershipDocument::create($val);
                    }
                }

                if ($t->houseCondition && !$sub->houseCondition){
                    $tmp = $t->houseCondition->getAttributes();
                    HouseCondition::create($tmp);
                    foreach ($t->houseCondition->houseProblemAreaPhoto as $key => $value) {
                        $val = $value->getAttributes();
                        HouseProblemAreaPhoto::create($val);
                    }
                }

                if ($t->accessBasicService && !$sub->accessBasicService){
                    $t->accessBasicService->returnRawPhoto = true;
                    $tmp = $t->accessBasicService->toArray();
                    AccessBasicService::create($tmp);
                }

                if ($t->foodConsumptionScore && !$sub->foodConsumptionScore){
                    $tmp = $t->foodConsumptionScore->toArray();
                    FoodConsumptionScore::create($tmp);
                }

                if ($t->householdStrategyFood && !$sub->householdStrategyFood){
                    $tmp = $t->householdStrategyFood->toArray();
                    HouseholdStrategyFood::create($tmp);
                }

                if ($t->communityAvailability && !$sub->communityAvailability){
                    $tmp = $t->communityAvailability->getAttributes();
                    CommunityAvailability::create($tmp);
                }

                if ($t->livelihood && !$sub->livelihood){
                    $tmp = $t->livelihood->toArray();
                    Livelihood::create($tmp);
                }

                if ($t->durableSolution && !$sub->durableSolution){
                    $tmp = $t->durableSolution->toArray();
                    DurableSolution::create($tmp);
                }

                if ($t->skillIdea && !$sub->skillIdea){
                    $tmp = $t->skillIdea->toArray();
                    SkillIdea::create($tmp);
                }

                if ($t->resettlement && !$sub->resettlement){
                    $tmp = $t->resettlement->toArray();
                    Resettlement::create($tmp);
                }
                if ($t->recentAssistance && !$sub->recentAssistance){
                    $tmp = $t->recentAssistance->toArray();
                    RecentAssistance::create($tmp);
                }
                if ($t->infrasttructureService && !$sub->infrasttructureService){
                    $tmp = $t->infrasttructureService->toArray();
                    InfrasttructureService::create($tmp);
                }
                if ($t->photoSection && !$sub->photoSection){
                    $tmp = $t->photoSection->getAttributes();
                    PhotoSection::create($tmp);
                }

                $t->delete();

                return $sub;
            }

            return false;
        });
        
    }

}