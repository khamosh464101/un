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
            $province = Province::where('name', $p['Province_Name'])->first();
            if (!$province) {
                $province = Province::create([
                    'name' => $p['Province_Name'],
                    'name_fa' => $p['Province_Name_Dari'], 
               ]);
            }

            if ($p['District_Type'] === 'District') {
                $district = District::create([
                    'name' => $p['District_Name'],
                    'name_fa' => $p['District_Name_Dari'], 
                    'is_urban' => true,
                    'province_id' => $province->id,

               ]);
            }
           
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
