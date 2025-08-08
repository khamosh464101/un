<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DurableSolution extends Model
{
    protected $table = "archive_dm_durable_solutions";
    protected $fillable = [
        'id',
        'future_families_preference',
        'local_integration_details',
        'local_integration_other',
        'do_you_have_land',
        'do_you_have_land_yes',
        'submission_id',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
