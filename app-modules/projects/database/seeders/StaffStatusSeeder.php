<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\StaffStatus;

class StaffStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['title' => 'Active', 'is_default' => true],
            ['title' => 'Inactive'],
            ['title' => 'On leave'],
            ['title' => 'Terminated'],
        ];

        foreach ($statuses as $key => $value) {
            StaffStatus::create($value);
        }
    }
}
