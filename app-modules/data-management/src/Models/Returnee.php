<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Returnee extends Model
{
    protected $table = "dm_returnees";
    protected $fillable = [
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

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function typeReturnDocumentPhoto(): HasMany
    {
        return $this->hasMany(TypeReturnDocomentPhoto::class, 'dm_returnee_id');
    }
}
