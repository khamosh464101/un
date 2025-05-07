<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    protected $fillable = ['_id', '_uuid', 'today', 'form_id'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function sourceInformation(): HasOne
    {
        return $this->hasOne(SourceInformation::class);
    }
}
