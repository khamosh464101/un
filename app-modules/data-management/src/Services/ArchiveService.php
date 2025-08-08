<?php
namespace Modules\DataManagement\Services;
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

class ArchiveService
{
    public function archiveSubmission(int $submissionId, int $userId) {
        return DB::transaction(function () use ($submissionId, $userId) {
            $submission = Submission::find($submissionId);
            $t = ArchiveSubmission::find($submissionId);
           
            if ($submission && !$t) {
                
                $tmp = $submission->toArray();
                $archivedAt = Carbon::now();
                $tmp['archived_by'] = $userId;
                $tmp['archived_at'] = $archivedAt;
                $as = ArchiveSubmission::create($tmp);

                if ($submission->sourceInformation && !$as->sourceInformation){
                    $tmp = $submission->sourceInformation->toArray();
                    ArchiveSourceInformation::create($tmp);
                }

                if ($submission->familyInformation && !$as->familyInformation){
                    $tmp = $submission->familyInformation->toArray();
                    ArchiveFamilyInformation::create($tmp);
                }
                

                if ($submission->headFamily && !$as->headFamily){
                    $submission->headFamily->returnRawPhoto = true;
                    $tmp = $submission->headFamily->toArray();
                    ArchiveHeadFamily::create($tmp);
                }

                if ($submission->interviewwee && !$as->interviewwee){
                    $submission->interviewwee->returnRawPhoto = true;
                    $tmp = $submission->interviewwee->toArray();
                    ArchiveInterviewwee::create($tmp);
                }

                if ($submission->composition && !$as->composition){
                    $tmp = $submission->composition->toArray();
                    ArchiveComposition::create($tmp);
                }


                if ($submission->idp && !$as->idp){
                    $tmp = $submission->idp->toArray();
                    ArchiveIdp::create($tmp);
                }
                if ($submission->returnee && !$as->returnee) {
                    $tmp = $submission->returnee->getAttributes();
                    ArchiveReturnee::create($tmp);
                
                    foreach ($submission->returnee->typeReturnDocumentPhoto as $documentPhoto) {
                        $val = $documentPhoto->getAttributes();
                        ArchiveTypeReturnDocumentPhoto::create($val);
                    }
                }

                if ($submission->extremelyVulnerableMember && !$as->extremelyVulnerableMember){
                    $tmp = $submission->extremelyVulnerableMember->toArray();
                    ArchiveExtremelyVulnerableMember::create($tmp);
                }

                if ($submission->accessCivilDocumentMale && !$as->accessCivilDocumentMale){
                    $tmp = $submission->accessCivilDocumentMale->toArray();
                    ArchiveAccessCivilDocumentMale::create($tmp);
                }
                if ($submission->accessCivilDocumentFemale && !$as->accessCivilDocumentFemale){
                    $tmp = $submission->accessCivilDocumentFemale->toArray();
                    ArchiveAccessCivilDocumentFemale::create($tmp);
                }
                if ($submission->houseLandOwnership && !$as->houseLandOwnership){
                    $tmp = $submission->houseLandOwnership->getAttributes();
                    ArchiveHouseLandOwnership::create($tmp);
                    foreach ($submission->houseLandOwnership->landOwnershipDocument as $key => $value) {
                        $val = $value->getAttributes();
                        ArchiveLandOwnershipDocument::create($val);
                    }
                }

                if ($submission->houseCondition && !$as->houseCondition){
                    $tmp = $submission->houseCondition->getAttributes();
                    ArchiveHouseCondition::create($tmp);
                    foreach ($submission->houseCondition->houseProblemAreaPhoto as $key => $value) {
                        $val = $value->getAttributes();
                        ArchiveHouseProblemAreaPhoto::create($val);
                    }
                }

                if ($submission->accessBasicService && !$as->accessBasicService){
                    $submission->accessBasicService->returnRawPhoto = true;
                    $tmp = $submission->accessBasicService->toArray();
                    ArchiveAccessBasicService::create($tmp);
                }

                if ($submission->foodConsumptionScore && !$as->foodConsumptionScore){
                    $tmp = $submission->foodConsumptionScore->toArray();
                    ArchiveFoodConsumptionScore::create($tmp);
                }

                if ($submission->householdStrategyFood && !$as->householdStrategyFood){
                    $tmp = $submission->householdStrategyFood->toArray();
                    ArchiveHouseholdStrategyFood::create($tmp);
                }

                if ($submission->communityAvailability && !$as->communityAvailability){
                    $tmp = $submission->communityAvailability->getAttributes();
                    ArchiveCommunityAvailability::create($tmp);
                }

                if ($submission->livelihood && !$as->livelihood){
                    $tmp = $submission->livelihood->toArray();
                    ArchiveLivelihood::create($tmp);
                }

                if ($submission->durableSolution && !$as->durableSolution){
                    $tmp = $submission->durableSolution->toArray();
                    ArchiveDurableSolution::create($tmp);
                }

                if ($submission->skillIdea && !$as->skillIdea){
                    $tmp = $submission->skillIdea->toArray();
                    ArchiveSkillIdea::create($tmp);
                }

                if ($submission->resettlement && !$as->resettlement){
                    $tmp = $submission->resettlement->toArray();
                    ArchiveResettlement::create($tmp);
                }
                if ($submission->recentAssistance && !$as->recentAssistance){
                    $tmp = $submission->recentAssistance->toArray();
                    ArchiveRecentAssistance::create($tmp);
                }
                if ($submission->infrasttructureService && !$as->infrasttructureService){
                    $tmp = $submission->infrasttructureService->toArray();
                    ArchiveInfrasttructureService::create($tmp);
                }
                if ($submission->photoSection && !$as->photoSection){
                    $tmp = $submission->photoSection->getAttributes();
                    ArchivePhotoSection::create($tmp);
                }

                $submission->delete();

                return $as;
            }

            return false;
            
            

        });
        
    }

}