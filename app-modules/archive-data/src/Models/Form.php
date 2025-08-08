<?php

namespace Modules\ArchiveData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $table = "archive_dm_forms";
    protected $fillable = ['id', 'form_id', 'title', 'raw_schema'];

    public function submissions (): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
