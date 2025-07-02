<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\StaffContractType;

class StaffContractTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['title' => 'Temporary', 'is_default' => true],
            ['title' => 'Perminant'],
        ];

        foreach ($types as $key => $value) {
            StaffContractType::create($value);
        }
    }
}
