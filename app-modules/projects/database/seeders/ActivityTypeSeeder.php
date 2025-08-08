<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\ActivityType;

class ActivityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['title' => 'Pre-implementation phase', 'is_default' => true],
            ['title' => 'Implementation phase'],
            ['title' => 'Post-implementation phase'],
        ];

        foreach ($types as $key => $value) {
            ActivityType::create($value);
        }
    }
}
