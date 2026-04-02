<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Clickbar\Magellan\Database\Eloquent\HasPostgisColumns;

class LandUse extends Model
{
    protected
        $table = 'dm_land_uses',
        $fillable = [
            'land_use_type',
            'geometry',
            'properties',
            'fill_color',
            'border_color',
            'fill_opacity',
            'symbology_rules',
            'shap_file_name'

        ];

    protected $casts = [
        'geometry' => 'array',
        'properties' => 'array',
        'symbology_rules' => 'array'
    ];

    protected $postgisColumns = [
        'geometry' => [
            'type' => 'geometry',
            'srid' => 4326
        ]
    ];

    public function updateSymbologyFromSettings()
    {
        $settings = SymbologySetting::where('land_use_type', $this->land_use_type)->first();
        
        if ($settings) {
            $this->fill_color = $settings->fill_color;
            $this->border_color = $settings->border_color;
            $this->fill_opacity = $settings->fill_opacity;
            $this->save();
        }
    }

}
