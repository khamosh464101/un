<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Storage;

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
        if ($this->returnRawPhoto) {
            return $value;
        }
        return $value ? asset("storage/kobo-attachments/$value") : null;
    }

    public function returnee(): BelongsTo
    {
        return $this->belongsTo(Returnee::class, 'dm_returnee_id');
    }

     public static function boot()
    {
         parent::boot();

        static::deleting(function ($typeReturnDocumentPhoto) {
            $photo = $typeReturnDocumentPhoto->getRawOriginal('type_return_document_photo');
            if (!is_null($photo)) {
                Storage::delete("kobo-attachments/$photo");
            }
        });
    }

}
