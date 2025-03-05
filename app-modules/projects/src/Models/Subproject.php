<?php

namespace Modules\Projects\Models;


use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Auth;

class Subproject extends Model
{
    protected $fillable = [
        'title',
        'budget',
        'announcement_date',
        'date_of_contract',
        'number_of_days',
        'description',
        'partner_id',
        'project_id',
        'subproject_type_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->useLogName('Subproject')
        ->logOnlyDirty()
        ->setDescriptionForEvent(fn(string $eventName) => "This Subproject has been {$eventName} by ". Auth::user()->name);;
        // Chain fluent methods for configuration options
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    public function type(): BelongsTo
    {
        return $this->belongsTo(SubprojectType::class);
    }


}
