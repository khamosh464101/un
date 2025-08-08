<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Projects\Notifications\Comments\CommentNotification;

use App\Models\User;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Log;

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
            if ($comment->ticket->owner->staff_id !== $comment->ticket->responsible_id) {
                $user;
                if ($comment->user_id == $comment->ticket->responsible->user->id) {
                    $user = $comment->ticket->owner;
                } else {
                    $user = $comment->ticket->responsible->user;
                }
                logger()->info('working in model');
                $user->notify(new CommentNotification($comment->toArray(), auth()->user(), 'added'));
                }
        });

        static::updated(function ($comment) {
            if ($comment->ticket->owner->staff_id !== $comment->ticket->responsible_id) {
                $user;
                if ($comment->user_id == $comment->ticket->responsible->user->id) {
                    $user = $comment->ticket->owner;
                } else {
                    $user = $comment->ticket->responsible->user;
                }
               

                $user->notify(new CommentNotification($comment->toArray(), auth()->user(), 'updated'));
            }
           
            
        });

        static::deleting(function ($comment) {
            if ($comment->ticket->owner->staff_id !== $comment->ticket->responsible_id) {
                $user;
                if ($comment->user_id == $comment->ticket->responsible->user->id) {
                    $user = $comment->ticket->owner;
                } else {
                    $user = $comment->ticket->responsible->user;
                }
                $tmpComment = [
                    'id' => $comment->id,
                    'ticket_id' => $comment->ticket_id,
                    'content' => $comment->content,
                ];
               $notification = $user->notify(new CommentNotification($tmpComment, auth()->user(), 'removed'));
            }
           
        });

    }

 


}
