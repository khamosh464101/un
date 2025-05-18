<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{

    protected $table = "dm_submissions";
    protected $fillable = [
        '_id', 
        '_uuid', 
        'start',
        'end',
        '__version__',
        '_submission_time',
        'consent',
        'status',
        'today', 
        'dm_form_id'
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
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

    public function houseHoldStrategyFood() : HasOne
    {
        return $this->hasOne(HouseHoldStrategyFood::class);
    }

    public function houseLandOwnerShip() : HasOne
    {
        return $this->hasOne(HouseLandOwnerShip::class);
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
        return $this->hasOne(PhotoSection::class);
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

}
