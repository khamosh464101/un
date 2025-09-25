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
use Modules\Projects\Notifications\Ticket\TicketNotification;

class Ticket extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title',
        'ticket_number',
        'description',
        'start_date',
        'deadline',
        'order',
        'order1',
        'owner_id',
        'responsible_id',
        'ticket_status_id',
        'ticket_priority_id',
        'activity_id',
     
    ];

    protected $appends = [
        'created_at_formatted', 
        'start_date_formatted', 
        'project_id', 
        'deadline_formatted', 
        'progress_percent',
        'progress_label'
    ];

   
    public function getActivitylogOptions(): LogOptions
    {
        $logable = $this->fillable;
        $logable = array_diff($logable, ['ticket_status_id', 'owner_id']);

        return LogOptions::defaults()
        ->logOnly($logable)
        ->useLogName('Ticket')
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn(string $eventName) => "This Task has been {$eventName} by ". Auth::user()->name);

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

    public function hours(): HasMany
    {
        return $this->hasMany(TicketHour::class, 'ticket_id', 'id');
    }


    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('M d, Y');
    }
    public function getStartDateFormattedAttribute() {
         $formattedDate = Carbon::parse($this->start_date)->format('Y-m-d');
         $relativeTime = Carbon::parse($this->start_date)->diffForHumans();
 
         // Return the formatted string
         return $formattedDate . ' (' . $relativeTime . ')';
    }

    public function getDeadlineFormattedAttribute() {
        $dueDate = Carbon::parse($this->deadline); // Replace with your due date
        $today = Carbon::now();

        $daysLeft = ceil($today->diffInDays($dueDate, false)); // false to return negative values

        if ($daysLeft > 0) {
            return "$daysLeft days left";
        } else {
            return  abs($daysLeft) . " days overdue";
        }
    }

    public function getProgressPercentAttribute() {
        $now = Carbon::now();
        $start = Carbon::parse($this->start_date)->startOfDay();
        $end = Carbon::parse($this->deadline)->endOfDay();
        if ($start->equalTo($end)) {
            return $now->greaterThanOrEqualTo($end) ? 100 : 0;
        }

        $totalDuration = $end->diffInSeconds($start);
        $elapsed = $now->diffInSeconds($start);

        $progress = ($elapsed / $totalDuration) * 100;
        return min(max(round($progress), 0), 100);
    }

    public function getProgressLabelAttribute() {
        $now = Carbon::now();
        $start = Carbon::parse($this->start_date)->startOfDay();
        $end = Carbon::parse($this->deadline)->endOfDay();

        $formatTimeDiff = function (Carbon $from, Carbon $to) {
            $diffInMinutes = $from->diffInMinutes($to);
            if ($diffInMinutes >= 1440) {
                return round($from->diffInDays($to)) . ' day' . ($from->diffInDays($to) > 1 ? 's' : '');
            } elseif ($diffInMinutes >= 60) {
                return round($from->diffInHours($to)) . ' hour' . ($from->diffInHours($to) > 1 ? 's' : '');
            } else {
                return round($diffInMinutes) . ' minute' . ($diffInMinutes > 1 ? 's' : '');
            }
        };

        if ($now->lt($start)) {
            return 'Starts in ' . $formatTimeDiff($now, $start);
        } elseif ($now->between($start, $end)) {
            return ($formatTimeDiff($now, $end) . ' left');
        } else {
            return 'Overdued';
        }
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
    public function getProjectIdAttribute()
    {
        return $this->activity->project_id;
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
            $ticket->ticket_number = $ticket->activity->activity_number . '-' . $ticket->id;
            $ticket->order = $ticket->activity->tickets()->max('order') + 1;
            $maxOrder = $ticket->activity->tickets()
                ->where('responsible_id', $ticket->responsible_id)
                ->max('order1');

            $ticket->order1 = ($maxOrder ?? 0) + 1;
            $ticket->save();

            if ($ticket->owner->staff_id !== $ticket->responsible_id) {
                $user;
                if (Auth::user()->id == $ticket->responsible->user->id) {
                    $user = $ticket->owner;
                } else {
                    $user = $ticket->responsible->user;
                }
                $user->notify(new TicketNotification($ticket->toArray(), auth()->user(), 'added'));
                }
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
                ->log("Task status changed from **$oldStatus** to **$newStatus** by " . auth()->user()->name);
            }

            if ($ticket->owner->staff_id !== $ticket->responsible_id) {
                $user;
                if (Auth::user()->id == $ticket->responsible->user->id) {
                    $user = $ticket->owner;
                } else {
                    $user = $ticket->responsible->user;
                }
                $user->notify(new TicketNotification($ticket->toArray(), auth()->user(), 'updated'));
                }
        });

        static::deleting(function ($ticket) {
            $ticket->documents()->delete();
            $ticket->comments()->delete();
            $ticket->hours()->delete();
            $ticket->gozars()->detach();

            if ($ticket->owner->staff_id !== $ticket->responsible_id) {
                $user;
                if (Auth::user()->id == $ticket->responsible->user->id) {
                    $user = $ticket->owner;
                } else {
                    $user = $ticket->responsible->user;
                }
                $ticket = [
                    'id' => $ticket->id,
                    'activity_id' => $ticket->activity_id,
                    'title' => $ticket->title,
                ];
               $notification = $user->notify(new TicketNotification($ticket, auth()->user(), 'removed'));
            }
        });

    }


}
