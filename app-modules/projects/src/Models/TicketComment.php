<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Carbon\Carbon;

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

}
