<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Projects\Notifications\Comments\CommentNotification;

use App\Models\User;
use Carbon\Carbon;
use Auth;

class TicketComment extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'content'
    ];

    public function ticket():BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCreatedAtAttribute($value)
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

        static::created(function ($comment) {
            $user;
            if ($comment->user_id == $comment->ticket->responsible->user->id) {
                $user = $comment->ticket->owner;
            } else {
                $user = $comment->ticket->responsible->user;
            }
            $user->notify(new CommentNotification($comment, auth()->user(), 'added'));
        });

        static::updating(function ($comment) {
            $user;
            if ($comment->user_id == $comment->ticket->responsible->user->id) {
                $user = $comment->ticket->owner;
            } else {
                $user = $comment->ticket->responsible->user;
            }
            $user->notify(new CommentNotification($comment, auth()->user(), 'updated'));
        });

        static::deleting(function ($comment) {
            $user;
            if ($comment->user_id == $comment->ticket->responsible->user->id) {
                $user = $comment->ticket->owner;
            } else {
                $user = $comment->ticket->responsible->user;
            }
           $notification = $user->notify(new CommentNotification($comment, auth()->user(), 'removed'));
        });

    }


}
