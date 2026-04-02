<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Storage;

class Parcel extends Model
{
        
    protected $table = 'dm_parcels';
    protected $fillable = [
        'parcel_code',
        'house_code',
        'geometry',
        'attributes',
        'land_use_type',
        'province',
        'district',
        'village',
        'area_sqm',
        'boundary_style',
        'shap_file_name'
    ];

    protected $casts = [
        'geometry' => 'array',
        'attributes' => 'array',
        'boundary_style' => 'array',

    ];

    public function getCenterAttribute()
    {
        if (!$this->geometry) return null;

        $coords = $this->geometry['coordinates'][0] ?? null;
        if (!$coords) return null;

        $lats = array_column($coords, 1);
        $lngs = array_column($coords, 0);

        return [
            'lat' => (min($lats) + max($lats)) / 2,
            'lng' => (min($lngs) + max($lngs)) / 2
        ];
    }

    /**
     * Get the image record for this parcel
     */
    public function image()
    {
        return $this->hasOne(ParcelImage::class, 'parcel_id');
    }
    
    /**
     * Check if parcel has a generated image
     */
    public function hasImage()
    {
        return $this->image && $this->image->isCompleted();
    }
    
    /**
     * Get image URL if exists
     */
    public function getImageUrlAttribute()
    {
        return $this->image?->image_url;
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($parcel) {

            if ($parcel->image) {
                $parcel->image->delete(); // ✅ triggers Image model boot() deleting
            }
        });
    }


}
