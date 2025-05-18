<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtremelyVulnerableMember extends Model
{
    protected $table = "dm_extremely_vulnerable_members";
    protected $fillable = [
        'large_Household',
        'disable_member',
        'physical_disable',
        'mental_disable',
        'chronic_disable',
        'drug_addicted',
        'conditional_women',
        'conditional_women_pregnant',
        'conditional_women_breastfeeding_mother',
        'conditional_women_widow',
        'drug_addicted',
        'drug_addicted',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
