<?php

namespace Modules\DataManagement\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\DataManagement\Models\SubmissionStatus;
class SubmissionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['title' => 'Draft', 'is_default' => true],
            ['title' => 'Approved'],
            ['title' => 'Need to validate'],
        ];

        foreach ($statuses as $key => $value) {
            SubmissionStatus::create($value);
        }
    }
}
