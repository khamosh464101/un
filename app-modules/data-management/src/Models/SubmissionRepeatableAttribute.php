<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

class SubmissionRepeatableAttribute extends Model
{
    protected $table = 'dm_submission_repeatable_attributes';

    protected $fillable = [
        'id',
        'attribute_name',
        'attribute_value',
        'submission_repeatable_group_id',
    ];


    public function attributes(): BelongsTo
    {
        return $this->belongsTo(SubmissionRepeatableGroup::class);
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($submissionRepeatableAttribute) {
            $attribute_value = $submissionRepeatableAttribute->getRawOriginal('attribute_value');

            if ($attribute_value) {
                $path = "kobo-attachments/{$attribute_value}";

                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
            }
        });
    }
    
}
