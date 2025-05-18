<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfrasttructureService extends Model
{
    protected $table = "dm_infrasttructure_services";
    protected $fillable = [
        'infrastructure_services_settlement',
        'submission_id',
    ];

    protected $casts = [
        'infrastructure_services_settlement' => 'array', // or 'json'
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
