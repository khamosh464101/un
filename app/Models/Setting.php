<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type'
    ];

    // public function getValueAttribute($value)
    // {
    //     if ($this->type === 'file') {
    //         return $value ? asset("storage/$value") : null;
    //     }
    //     return $value;
    // }
}
