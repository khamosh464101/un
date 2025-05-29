<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeReturnDocumentPhoto extends Model
{
    protected $table = "dm_type_return_document_photos";
    protected $fillable = [
        'id',
        'type_return_document_photo',
        'dm_returnee_id',
    ];

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public bool $returnRawPhoto = false;

    public function getTypeReturnDocumentPhotoAttribute($value)
    {
        if ($returnRawPhoto) {
            return $value;
        }
        $tmpName = $this->returnee->submission->_id . '-' . $value;
        return $value ? asset("storage/kobo-attachments/$tmpName") : asset('import/assets/post-pic-dummy.png');
    }

    public function returnee(): BelongsTo
    {
        return $this->belongsTo(Returnee::class, 'dm_returnee_id');
    }
}
