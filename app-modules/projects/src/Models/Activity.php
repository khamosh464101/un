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
        'activity_status_id',
        'activity_type_id',
        'staff_id'
    ];

}
