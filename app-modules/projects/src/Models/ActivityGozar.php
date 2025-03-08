<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityGozar extends Model
{
    protected $table = 'activity_gozar';
    protected $fillable = [
        'gozar_id',
        'activity_id'
    ];


}
