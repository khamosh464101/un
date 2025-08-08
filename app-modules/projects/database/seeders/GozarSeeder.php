<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\Province;
use Modules\Projects\Models\District;
use Modules\Projects\Models\Gozar;

class GozarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/data/villages.json');
        $jsonData = file_get_contents($filePath);
        $dataArray = json_decode($jsonData, true);

        foreach($dataArray as $key => $v) {
            $p = Province::where('name', $v['Province'])->first();
            if ($p) {
                $d = $p->districts->where('name', $v['District'])->first();
                if ($d) {
                    Gozar::create([
                        'name' => $v['Village Name'],
                        'latitude' => $v['Latitude'],
                        'longitude' => $v['Longitude'],
                        'district_id' => $d->id
                      
                   ]);
                }
            }

        }
    }
}
