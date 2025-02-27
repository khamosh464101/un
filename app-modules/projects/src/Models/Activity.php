<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        "title",
        'start_date',
        'end_date',
        'description',
        'project_id',
        'status_id',
        'type_id',
        'gozar_id',
        'staff_id'
    ];

}
