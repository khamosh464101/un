<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Projects\Models\Project;
use Modules\DataManagement\Models\SubmissionStatus;


class Submission extends Model
{

    protected $table = "archive_dm_submissions";
    protected $fillable = [
        'id',
        '_id', 
        '_uuid', 
        'start',
        'end',
        '__version__',
        '_submission_time',
        'consent',
        'status',
        'today', 
        'dm_form_id',
        'archived_at',
        'archived_by',
        'submission_status_id'
    ];

    protected $appends = ['map_image'];


    public function getMapImageAttribute()
    {
        $tmpName = $this->_id . '.jpg';
        return $tmpName ? asset("storage/gis/$tmpName") : null;
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_submission');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function dstatus(): BelongsTo
    {
        return $this->belongsTo(SubmissionStatus::class, 'submission_status_id');
    }

    public function sourceInformation(): HasOne
    {
        return $this->hasOne(SourceInformation::class);
    }

    public function accessBasicService() : HasOne
    {
        return $this->hasOne(AccessBasicService::class);
    }

    public function accessCivilDocumentFemale() : HasOne
    {
        return $this->hasOne(AccessCivilDocumentFemale::class);
    }

    public function accessCivilDocumentMale() : HasOne
    {
        return $this->hasOne(AccessCivilDocumentMale::class);
    }

    public function communityAvailability() : HasOne
    {
        return $this->hasOne(CommunityAvailability::class);
    }

    public function composition() : HasOne
    {
        return $this->hasOne(Composition::class);
    }

     public function durableSolution() : HasOne
    {
        return $this->hasOne(DurableSolution::class);
    }

     public function extremelyVulnerableMember() : HasOne
    {
        return $this->hasOne(ExtremelyVulnerableMember::class);
    }

    public function familyInformation() : HasOne
    {
        return $this->hasOne(FamilyInformation::class);
    }

    public function foodConsumptionScore() : HasOne
    {
        return $this->hasOne(FoodConsumptionScore::class);
    }

     public function headFamily() : HasOne
    {
        return $this->hasOne(HeadFamily::class, 'submission_id');
    }

     public function houseCondition() : HasOne
    {
        return $this->hasOne(HouseCondition::class);
    }

    public function householdStrategyFood() : HasOne
    {
        return $this->hasOne(HouseholdStrategyFood::class);
    }

    public function houseLandOwnership() : HasOne
    {
        return $this->hasOne(HouseLandOwnership::class);
    }

    public function idp() : HasOne
    {
        return $this->hasOne(Idp::class);
    }

     public function infrasttructureService() : HasOne
    {
        return $this->hasOne(InfrasttructureService::class);
    }

     public function interviewwee() : HasOne
    {
        return $this->hasOne(Interviewwee::class);
    }


     public function livelihood() : HasOne
    {
        return $this->hasOne(Livelihood::class);
    }

     public function photoSection() : HasOne
    {
        return $this->hasOne(PhotoSection::class, 'submission_id');
    }

    public function recentAssistance() : HasOne
    {
        return $this->hasOne(RecentAssistance::class);
    }

    public function resettlement() : HasOne
    {
        return $this->hasOne(Resettlement::class);
    }

    public function returnee() : HasOne
    {
        return $this->hasOne(Returnee::class);
    }

    public function skillIdea() : HasOne
    {
        return $this->hasOne(SkillIdea::class);
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($submission) {
            $submission->sourceInformation()->delete(); // Delete all related documents in one query
            $submission->familyInformation()->delete();
            $submission->headFamily()->delete();
            $submission->interviewwee()->delete();
            $submission->composition()->delete();
            $submission->idp()->delete();
            foreach ($submission->returnee?->typeReturnDocumentPhoto ?? [] as $value) {
                $value->delete();
            }
            $submission->returnee()->delete();
            $submission->extremelyVulnerableMember()->delete();
            $submission->accessCivilDocumentMale()->delete();
            $submission->accessCivilDocumentFemale()->delete();
            foreach ($submission->houseLandOwnership?->landOwnershipDocument ?? [] as $key => $value) {
                $value->delete();
            }
            $submission->houseLandOwnership()->delete();
            foreach ($submission->houseCondition?->houseProblemAreaPhoto ?? [] as $key => $value) {
                $value->delete();
            }
            $submission->houseCondition()->delete();
            $submission->accessBasicService()->delete();
            $submission->foodConsumptionScore()->delete();
            $submission->householdStrategyFood()->delete();
            $submission->communityAvailability()->delete();
            $submission->livelihood()->delete();
            $submission->durableSolution()->delete();
            $submission->skillIdea()->delete();
            $submission->infrasttructureService()->delete();
            $submission->photoSection()->delete();
        });
    }


}
