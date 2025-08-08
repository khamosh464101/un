<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\SubprojectType;

class SubprojectTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['title' => 'Shelter Repair', 'is_default' => true],
        ];

        foreach ($types as $key => $value) {
            SubprojectType::create($value);
        }
    }
}
