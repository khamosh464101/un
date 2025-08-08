<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Returnee extends Model
{
    protected $table = "dm_returnees";
    protected $fillable = [
        'id',
        'year_returnee',
        'migrate_country',
        'migrate_country_other',
        'migration_reason',
        'migration_reason_security',
        'migration_reason_natural_disaster',
        'migration_reason_other',
        'duration_Household_living_there',
        "date_return_home_country",
        'entry_borders',
        'return_document_have',
        'type_return_document',
        'type_return_document_number',
        "type_return_document_date",
        "household_get_support_no",
        'household_get_support',
        'household_get_support_yes',
        'organization_support',
        'reason_return',
        'return_reason_force',
        'return_reason_voluntair',
        'submission_id',
    ];

    public function getIgnoreIdFillable()
    {
        return array_filter(parent::getFillable(), function ($field) {
            return $field !== 'id';
        });
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function typeReturnDocumentPhoto(): HasMany
    {
        return $this->hasMany(TypeReturnDocumentPhoto::class, 'dm_returnee_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($submission) {
            $submission->typeReturnDocumentPhoto()->delete(); // Delete all related documents in one query
        });
    }

    
}
