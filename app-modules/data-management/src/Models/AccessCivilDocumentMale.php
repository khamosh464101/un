<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessCivilDocumentMale extends Model
{
    protected $table = "dm_access_civil_document_males";
    protected $fillable = [
        'id',
        'access_civil_documentation_male_tazkira',
        'access_civil_documentation_male_birthcertificate',
        'access_civil_documentation_male_marriagecertificate',
        'access_civil_documentation_male_departationcard',
        'access_civil_documentation_male_drivinglicense',
        'submission_id',
    ];

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
