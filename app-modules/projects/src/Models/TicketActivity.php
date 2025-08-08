<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketActivity extends Model
{
    protected $fillable = [
        'ticket_id', 'old_status_id', 'new_status_id', 'user_id'
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id')->withTrashed();
    }

    public function oldStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'old_status_id', 'id')->withTrashed();
    }

    public function newStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'new_status_id', 'id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
}
