<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Modules\Projects\Notifications\HourNotification;


class TicketHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'ticket_id', 'value', 'comment', 'title'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function forHumans(): Attribute
    {
        return new Attribute(
            get: function () {
                $seconds = $this->value * 3600;
                return CarbonInterval::seconds($seconds)->cascade()->forHumans();
            }
        );
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($hour) {
            if ($hour->ticket->owner->staff_id !== $hour->ticket->responsible_id) {
                $user;
                if ($hour->user_id == $hour->ticket->responsible->user->id) {
                    $user = $hour->ticket->owner;
                } else {
                    $user = $hour->ticket->responsible->user;
                }
                $user->notify(new HourNotification($hour, auth()->user(), 'added'));
                }
        });

        static::updated(function ($hour) {
            if ($hour->ticket->owner->staff_id !== $hour->ticket->responsible_id) {
                $user;
                if ($hour->user_id == $hour->ticket->responsible->user->id) {
                    $user = $hour->ticket->owner;
                } else {
                    $user = $hour->ticket->responsible->user;
                }
                $user->notify(new HourNotification($hour, auth()->user(), 'updated'));
            }
           
            
        });

        static::deleting(function ($hour) {
            if ($hour->ticket->owner->staff_id !== $hour->ticket->responsible_id) {
                $user;
                if ($hour->user_id == $hour->ticket->responsible->user->id) {
                    $user = $hour->ticket->owner;
                } else {
                    $user = $hour->ticket->responsible->user;
                }
                $tmpHour = [
                    'id' => $hour->id,
                    'ticket_id' => $hour->ticket_id,
                    'title' => $hour->title,
                ];
               $notification = $user->notify(new HourNotification($tmpHour, auth()->user(), 'removed'));
            }
           
        });

    }
}
