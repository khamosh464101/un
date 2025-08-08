<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $table = "dm_forms";
    protected $fillable = ['form_id', 'title', 'raw_schema'];

    public function submissions (): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
