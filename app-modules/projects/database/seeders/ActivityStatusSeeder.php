<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\ActivityStatus;

class ActivityStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['title' => 'Not started', 'is_default' => true],
            ['title' => 'In progress'],
            ['title' => 'Completed'],
            ['title' => 'On hold'],
            ['title' => 'Canceled'],
        ];

        foreach ($statuses as $key => $value) {
            ActivityStatus::create($value);
        }
    }
}
