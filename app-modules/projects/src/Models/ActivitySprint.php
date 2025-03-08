<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySprint extends Model
{
    protected $fillable = ['started_at', 'ended_at', 'activity_id'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

}
