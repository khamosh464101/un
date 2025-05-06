<?php

namespace Modules\Projects\Models;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Activity as Actvty;
use App\Models\User;
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
        'end_of_contract',
        'gender',
        'about',
        'staff_status_id',
        'staff_contract_type_id'
    ];

    protected $appends = ['created_at_formatted', 'updated_at_formatted'];

    public function getActivitylogOptions(): LogOptions
    {
        $logable = $this->fillable;
        $logable = array_diff($logable, ['staff_status_id']);
        $name = Auth::user()->name ?? null;
        return LogOptions::defaults()
        ->logOnly($logable)
        ->useLogName('Staff')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn(string $eventName) => "This Staff has been {$eventName} by ". $name);

    }



    public function logs(): HasMany
    {
        return $this->hasMany(Actvty::class, 'subject_id')->where('log_name', 'Staff')->orderBy('id', 'desc');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StaffStatus::class, 'staff_status_id');
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(StaffContractType::class, 'staff_contract_type_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function manages(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function getPhotoAttribute($value)
    {
        return $value ? asset("storage/$value") : ($this->gender === 'Female' ? asset('avatar/female.jpg') : asset('avatar/male.jpg'));
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

    public function getCreatedAtFormattedAttribute()
    {
        // Format the 'created_at' value
        $formattedDate = Carbon::parse($this->created_at)->format('Y-m-d h:i A');

        // Get the human-readable relative time (e.g., "3 hours ago")
        $relativeTime = Carbon::parse($this->created_at)->diffForHumans();

        // Return the formatted string
        return $formattedDate . ' (' . $relativeTime . ')';
    }

    public function getUpdatedAtFormattedAttribute()
    {
        // Format the 'created_at' value
        $formattedDate = Carbon::parse($this->updated_at)->format('Y-m-d h:i A');

        // Get the human-readable relative time (e.g., "3 hours ago")
        $relativeTime = Carbon::parse($this->updated_at)->diffForHumans();

        // Return the formatted string
        return $formattedDate . ' (' . $relativeTime . ')';
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'staff_id');
    }
    
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class);
    }

    public function tickets (): HasMany
    {
        return $this->hasMany(Ticket::class, 'responsible_id');
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
            $staff->projects()->detach();
            $staff->user()->delete();
            $staff->documents()->delete(); // Delete all related documents in one query

        });
    }

}
