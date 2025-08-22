<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

class Interviewwee extends Model
{
    protected $table = "dm_interviewwees";
    
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

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public bool $returnRawPhoto = false;

    public function getInterNicPhotoAttribute($value)
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

        static::deleting(function ($interviewwee) {
            $photo = $interviewwee->getRawOriginal('inter_nic_photo');

            if (!is_null($photo)) {
                Storage::delete("kobo-attachments/$photo");
            }
        });
    }

}
