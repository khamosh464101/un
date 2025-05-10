<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessCivilDocumentMale extends Model
{
    protected $fillable = [
        'access_civil_documentation_male_tazkira',
        'access_civil_documentation_male_birthcertificate',
        'access_civil_documentation_male_marriagecertificate',
        'access_civil_documentation_male_departationcard',
        'access_civil_documentation_male_drivinglicense',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
