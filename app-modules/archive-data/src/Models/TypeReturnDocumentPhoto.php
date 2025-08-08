<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeReturnDocumentPhoto extends Model
{
    protected $table = "archive_dm_type_return_document_photos";
    protected $fillable = [
        'id',
        'type_return_document_photo',
        'dm_returnee_id',
    ];

    public bool $returnRawPhoto = false;

    public function getTypeReturnDocumentPhotoAttribute($value)
    {
        if ($returnRawPhoto) {
            return $value;
        }
        $tmpName = $this->returnee->submission->_id . '-' . $value;
        return $value ? asset("storage/kobo-attachments/$tmpName") : null;
    }

    public function returnee(): BelongsTo
    {
        return $this->belongsTo(Returnee::class, 'dm_returnee_id');
    }
}
