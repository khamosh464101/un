<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\ProjectStatus;

class ProjectStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['title' => 'Proposed'],
            ['title' => 'Approved', 'is_default' => true],
            ['title' => 'In progress'],
            ['title' => 'On hold'],
            ['title' => 'Completed'],
            ['title' => 'Canceled'],
        ];

        foreach ($statuses as $key => $value) {
            ProjectStatus::create($value);
        }
    }
}
