<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessCivilDocumentFemale extends Model
{
    
    protected $table = "archive_dm_access_civil_document_females";
    protected $fillable = [
        'id',
        'access_civil_documentation_female_tazkira',
        'access_civil_documentation_female_birthcertificate',
        'access_civil_documentation_female_marriagecertificate',
        'access_civil_documentation_female_departationcard',
        'access_civil_documentation_female_drivinglicense',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
