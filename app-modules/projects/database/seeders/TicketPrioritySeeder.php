<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\TicketPriority;
class TicketPrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            ['title' => 'High'],
            ['title' => 'Normal', 'is_default' => true],
            ['title' => 'Low'],
        ];

        foreach ($priorities as $key => $value) {
            TicketPriority::create($value);
        }
    }
}
