<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
// use App\Notifications\TicketCreated;
// use App\Notifications\TicketStatusUpdated;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Models\Activity as Actvty;
use Carbon\Carbon;
use App\Models\User;
use Auth;

class Ticket extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title',
        'ticket_number',
        'description',
        'estimation',
        'deadline',
        'order',
        'owner_id',
        'responsible_id',
        'ticket_status_id',
        'ticket_type_id',
        'ticket_priority_id',
        'activity_id'
    ];

    protected $appends = ['created_at_formatted'];

   
    public function getActivitylogOptions(): LogOptions
    {
        $logable = $this->fillable;
        $logable = array_diff($logable, ['ticket_status_id', 'owner_id']);

        return LogOptions::defaults()
        ->logOnly($logable)
        ->useLogName('Ticket')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn(string $eventName) => "This Ticket has been {$eventName} by ". Auth::user()->name);

    }

    public function logs(): HasMany
    {
        return $this->hasMany(Actvty::class, 'subject_id')->where('log_name', 'Ticket')->orderBy('id', 'desc');
    }
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id', 'id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id', 'id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'ticket_priority_id', 'id');
    }

    public function gozars(): BelongsToMany
    {
        return $this->belongsToMany(Gozar::class);
    }   
    
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }


    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class, 'ticket_id', 'id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'ticket_id', 'id');
    }


    public function relations(): HasMany
    {
        return $this->hasMany(TicketRelation::class, 'ticket_id', 'id');
    }

    public function hours(): HasMany
    {
        return $this->hasMany(TicketHour::class, 'ticket_id', 'id');
    }


    public function totalLoggedHours(): Attribute
    {
        return new Attribute(
            get: function () {
                $seconds = $this->hours->sum('value') * 3600;
                return CarbonInterval::seconds($seconds)->cascade()->forHumans();
            }
        );
    }

    public function totalLoggedSeconds(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->hours->sum('value') * 3600;
            }
        );
    }

    public function totalLoggedInHours(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->hours->sum('value');
            }
        );
    }

    public function estimationForHumans(): Attribute
    {
        return new Attribute(
            get: function () {
                return CarbonInterval::seconds($this->estimationInSeconds)->cascade()->forHumans();
            }
        );
    }

    public function estimationInSeconds(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->estimation) {
                    return null;
                }
                return $this->estimation * 3600;
            }
        );
    }

    public function estimationProgress(): Attribute
    {
        return new Attribute(
            get: function () {
                return (($this->totalLoggedSeconds ?? 0) / ($this->estimationInSeconds ?? 1)) * 100;
            }
        );
    }

    public function completudePercentage(): Attribute
    {
        return new Attribute(
            get: fn() => $this->estimationProgress
        );
    }

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }

    public function getDeadlineAttribute($value) {
        $dueDate = Carbon::parse($value); // Replace with your due date
        $today = Carbon::now();

        $daysLeft = ceil($today->diffInDays($dueDate, false)); // false to return negative values

        if ($daysLeft > 0) {
            return "$daysLeft days left";
        } else {
            return  abs($daysLeft) . " days overdue";
        }
    }


    public function getCreatedAtFormattedAttribute($value)
    {
        // Format the 'created_at' value
        $formattedDate = Carbon::parse($value)->format('Y-m-d h:i A');

        // Get the human-readable relative time (e.g., "3 hours ago")
        $relativeTime = Carbon::parse($value)->diffForHumans();

        // Return the formatted string
        return $formattedDate . ' (' . $relativeTime . ')';
    }

    public function getUpdatedAtAttribute($value)
    {
        // Format the 'created_at' value
        $formattedDate = Carbon::parse($value)->format('Y-m-d h:i A');

        // Get the human-readable relative time (e.g., "3 hours ago")
        $relativeTime = Carbon::parse($value)->diffForHumans();

        // Return the formatted string
        return $formattedDate . ' (' . $relativeTime . ')';
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($ticket) {
            $ticket->ticket_number = '#' . $ticket->activity->activity_number . '-' . $ticket->id;
            $ticket->order = $ticket->activity->tickets()->max('order') + 1;
            $ticket->save();
        });

        static::updating(function ($ticket) {
            if ($ticket->isDirty('ticket_status_id')) {
                $oldStatus = TicketStatus::find($ticket->getOriginal('ticket_status_id'))->title ?? 'Unknown';
                $newStatus = TicketStatus::find($ticket->ticket_status_id)->title ?? 'Unknown';
                activity()
                ->useLog('Ticket')
                ->causedBy(auth()->user()) // Log who made the change
                ->performedOn($ticket)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ])
                ->log("Ticket status changed from **$oldStatus** to **$newStatus** by " . auth()->user()->name);
            }
        });

        static::deleting(function ($ticket) {
            $ticket->documents()->delete(); // Delete all related documents in one query
            $ticket->comments()->delete();
            $ticket->gozars()->detach();
        });
    }


}
