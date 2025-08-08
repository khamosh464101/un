<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\Province;
use Modules\Projects\Models\District;
use Modules\Projects\Models\Gozar;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/data/un-province-and-district-list.json');
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
                $district = District::where('name', $p['District_Name'])->first();
                $district = District::create([
                    'name' => $p['District_Name'],
                    'name_fa' => $p['District_Name_Dari'], 
                    'is_urban' => false,
                    'province_id' => $province->id,

               ]);
            }
           

        }

        

        $this->createNahiaAndGozar('app/data/kabul-nahia-gozar.json', 'Kabul');
        $this->createNahiaAndGozar('app/data/kandahar-nahia-gozar.json', 'Kandahar');
        $this->createNahiaAndGozar('app/data/herat-nahia-gozar.json', 'Hirat');
        $this->createNahiaAndGozar('app/data/farah-nahia-gozar.json', 'Farah');
        $this->createNahiaAndGozar('app/data/bamyan-nahia-gozar.json', 'Bamyan');
        $this->createNahiaAndGozar('app/data/daykundi-nahia-gozar.json', 'Daykundi');
        $this->createNahiaAndGozar('app/data/mazar-sharif-nahia-gozar.json', 'Balkh');
        $this->createNahiaAndGozar('app/data/nangarhar-nahia-gozar.json', 'Nangarhar');

    }
    
    function createNahiaAndGozar($jsonPath, $provinceName) {
        $filePath = storage_path($jsonPath);
        $jsonData = file_get_contents($filePath);
        $dataArray = json_decode($jsonData, true);
        $province = Province::where('name', $provinceName)->first();
        foreach($dataArray as $key => $p) {
            $district = District::where('name', $p['District Code (Nahia)'])->where('province_id', $province->id)->first();
            if ($province && ! $district) {
                $district = District::create([
                    'name' => $p['District Code (Nahia)'],
                    'name_fa' => $p['District Code (Nahia)'], 
                    'is_urban' => true,
                    'province_id' => $province->id,

               ]);
            }

            if($district) {
                Gozar::create(['name' => $p['Gozar Code'], 'district_id' => $district->id]);
            }
        }
    }
}
