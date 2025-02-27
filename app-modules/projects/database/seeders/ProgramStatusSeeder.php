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
        $programs = [
            ['title' => 'Planning/Initiation'],
            ['title' => 'Activity/Ongoing'],
            ['title' => 'Paused'],
            ['title' => 'Completed'],
            ['title' => 'On Hold'],
            ['title' => 'Cancelled'],
            ['title' => 'Closed'],
        ];

        foreach ($programs as $key => $value) {
            ProgramStatus::create($value);
        }
    }
}
