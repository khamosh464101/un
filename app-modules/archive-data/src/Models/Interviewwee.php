<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interviewwee extends Model
{
    protected $table = "archive_dm_interviewwees";
    
    protected $fillable = [
        'id',
        'interviewee_hof_relation',
        'inter_name',
        'inter_father_name',
        'inter_grandfather_name',
        'inter_phone_number',
        'does_inter_have_nic',
        'inter_nic_number',
        'inter_nic_photo',
        'inter_sex',
        'inter_age',
        'submission_id',
    ];

    public bool $returnRawPhoto = false;

    public function getInterNicPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        $folderName = $this->submission?->projects?->first()?->id;
        return $value ? asset("storage/kobo-attachments/$folderName/$value") : null;
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }


}
