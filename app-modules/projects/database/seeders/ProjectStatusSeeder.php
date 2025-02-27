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
        $projects = [
            ['title' => 'Not Started'],
            ['title' => 'In Progress'],
            ['title' => 'Delayed'],
            ['title' => 'At Risk'],
            ['title' => 'Completed'],
            ['title' => 'On Hold'],
            ['title' => 'Canceled'],
            ['title' => 'Needs Attention'],
            ['title' => 'Finished but Pending Review'],
        ];

        foreach ($projects as $key => $value) {
            ProjectStatus::create($value);
        }
    }
}
