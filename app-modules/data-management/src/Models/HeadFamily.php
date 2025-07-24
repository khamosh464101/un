<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage; 

class HeadFamily extends Model
{
    protected $table = "dm_head_families";
    
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

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public bool $returnRawPhoto = false;

    public function getHohNicPhotoAttribute($value)
    {
        if ($this->returnRawPhoto) {
            return $value;
        }
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public static function boot()
    {
         parent::boot();

        static::deleting(function ($headFamily) {
            $photo = $headFamily->getRawOriginal('hoh_nic_photo');
            if (!is_null($photo)) {
                Storage::delete("kobo-attachments/$photo");
            }
        });
    }



}
