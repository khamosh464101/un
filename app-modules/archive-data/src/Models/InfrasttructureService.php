<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfrasttructureService extends Model
{
    protected $table = "archive_dm_infrasttructure_services";
    protected $fillable = [
        'id',
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
