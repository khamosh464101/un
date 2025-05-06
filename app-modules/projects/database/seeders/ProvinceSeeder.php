<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\Province;
use Modules\Projects\Models\District;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/data/provinces-and-districts.json');
        $jsonData = file_get_contents($filePath);
        $dataArray = json_decode($jsonData, true);

        foreach($dataArray as $key => $p) {
           $province = Province::create([
                'name' => $p['name'],
                'name_fa' => $p['nameFa'],
                'name_pa' => $p['namePa'],
                'latitude' => $p['latitude'],
                'longitude' => $p['longitude'],
              
           ]);
        //    foreach ($p['districts'] as $key => $d) {
        //     $district = District::create([
        //         'name' => $d['name'],
        //         'name_fa' => $d['nameFa'],
        //         'name_pa' => $d['namePa'],
        //         'latitude' => $d['latitude'],
        //         'longitude' => $d['longitude'],
        //         'province_id' => $province->id,
              
        //    ]);
        //    }

        }
    }
}
