<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Activity as Actvty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Storage;
class Staff extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'position_title',
        'personal_email',
        'official_email',
        'photo',
        'phone1',
        'phone2',
        'duty_station',
        'date_of_joining',
        'about',
        'staff_status_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $logable = $this->fillable;
        $logable = array_diff($logable, ['staff_status_id']);

        return LogOptions::defaults()
        ->logOnly($logable)
        ->useLogName('Staff')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn(string $eventName) => "This Staff has been {$eventName} by ". Auth::user()->name);

    }



    public function logs(): HasMany
    {
        return $this->hasMany(Actvty::class, 'subject_id')->where('log_name', 'Staff')->orderBy('id', 'desc');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StaffStatus::class, 'staff_status_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function getPhotoAttribute($value)
    {
        return $value ? asset("storage/$value") : asset('import/assets/post-pic-dummy.png');
    }
    public function getDateOfJoiningAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }



    public static function boot()
    {
        parent::boot();
        static::updating(function ($staff) {
            if ($staff->isDirty('photo') && !is_null($staff->getRawOriginal('photo'))) {
                Storage::delete($staff->getRawOriginal('photo'));
            }

            if ($staff->isDirty('staff_status_id')) {
                $oldStatus = StaffStatus::find($staff->getOriginal('staff_status_id'))->title ?? 'Unknown';
                $newStatus = StaffStatus::find($staff->staff_status_id)->title ?? 'Unknown';
                activity()
                ->useLog('Staff')
                ->causedBy(auth()->user()) // Log who made the change
                ->performedOn($staff)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ])
                ->log("Staff status changed from **$oldStatus** to **$newStatus** by " . auth()->user()->name);
            }

        });
        static::deleting(function ($staff) {
            if (!is_null($staff->getRawOriginal('photo'))) {
                Storage::delete($staff->getRawOriginal('photo'));
            }
            $staff->documents()->delete(); // Delete all related documents in one query

        });
    }

}
