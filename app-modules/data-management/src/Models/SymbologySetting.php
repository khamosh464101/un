<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;

class SymbologySetting extends Model
{
    protected $fillable = [
        'land_use_type',
        'fill_color',
        'border_color',
        'fill_opacity',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public static function getDefaultSymbology()
    {
        return [
            'Residential' => ['fill_color' => '#3B82F6', 'border_color' => '#1E3A8A', 'fill_opacity' => 0.6],
            'Commercial' => ['fill_color' => '#EF4444', 'border_color' => '#991B1B', 'fill_opacity' => 0.6],
            'Park' => ['fill_color' => '#10B981', 'border_color' => '#065F46', 'fill_opacity' => 0.5],
            'Flood' => ['fill_color' => '#06b6d4', 'border_color' => '#0e7490', 'fill_opacity' => 0.5],
            'Earthquake' => ['fill_color' => '#F59E0B', 'border_color' => '#92400E', 'fill_opacity' => 0.5],
            'Landslide' => ['fill_color' => '#8B5CF6', 'border_color' => '#5B21B6', 'fill_opacity' => 0.5],
            'Industrial' => ['fill_color' => '#6B7280', 'border_color' => '#374151', 'fill_opacity' => 0.6],
            'Agriculture' => ['fill_color' => '#84CC16', 'border_color' => '#3F6212', 'fill_opacity' => 0.5],
            'Water' => ['fill_color' => '#06b6d4', 'border_color' => '#0284c7', 'fill_opacity' => 0.7],
        ];
    }
}
