<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

class SubmissionExtraAttribute extends Model
{
    protected $table = 'dm_submission_extra_attributes';
    
    protected $fillable = [
        'id',
        'attribute_name',
        'attribute_value',
        'submission_id'

    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($submissionExtraAttribute) {
            $attibute_value = $submissionExtraAttribute->getRawOriginal('attribute_value');

            if ($attibute_value) {
                $path = "kobo-attachments/{$attibute_value}";

                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
            }
        });
    }
}
