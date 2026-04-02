<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;

class ParcelStyle extends Model
{
    protected $table = 'dm_parcel_styles';
    protected $fillable = [
        'name',
        'selected_color',
        'selected_weight',
        'selected_style',
        'selected_opacity',
        'other_color',
        'other_weight',
        'other_style',
        'other_opacity',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public static function getActiveStyle()
    {
        return self::where('is_active', true)->first() ?? self::createDefault();
    }

    public static function createDefault()
    {
        return self::create([
            'name' => 'default',
            'selected_color' => '#FF0000', // Red
            'selected_weight' => 3,
            'selected_style' => 'solid',
            'selected_opacity' => 1.0,
            'other_color' => '#CCCCCC', // Grey
            'other_weight' => 1,
            'other_style' => 'solid',
            'other_opacity' => 0.3,
            'is_active' => true
        ]);
    }
}
