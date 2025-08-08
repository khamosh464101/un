<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\ProgramStatus;

class ProgramStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['title' => 'Planned', 'is_default' => true],
            ['title' => 'Active'],
            ['title' => 'On hold'],
            ['title' => 'Completed'],
            ['title' => 'Cancelled'],
        ];

        foreach ($statuses as $key => $value) {
            ProgramStatus::create($value);
        }
    }
}
