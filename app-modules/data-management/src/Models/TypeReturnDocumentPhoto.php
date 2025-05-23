<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeReturnDocumentPhoto extends Model
{
    protected $table = "dm_type_return_document_photos";
    protected $fillable = [
        'type_return_document_photo',
        'dm_returnee_id',
    ];

    public function getTypeReturnDocumentPhotoAttribute($value)
    {
        $tmpName = $value->returnee->submission->_id . '-' . $value;
        return $value ? asset("storage/kobo-attachments/$tmpName") : asset('import/assets/post-pic-dummy.png');
    }

    public function returnee(): BelongsTo
    {
        return $this->belongsTo(Returnee::class, 'dm_returnee_id');
    }
}
