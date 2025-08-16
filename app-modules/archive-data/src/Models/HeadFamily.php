<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeadFamily extends Model
{
    protected $table = "archive_dm_head_families";
    protected $fillable = [
        'id',
        'hoh_name',
        'hoh_father_name',
        'hoh_grandfather_name',
        'hoh_phone_number',
        'does_hoh_have_nic',
        'hoh_nic_number',
        'hoh_nic_photo',
        'hoh_sex',
        'hoh_age',
        'submission_id',
    ];

    public bool $returnRawPhoto = false;

    public function getHohNicPhotoAttribute($value)
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
