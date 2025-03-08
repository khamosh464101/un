<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\TicketStatus;
class TicketStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['title' => 'Open', 'is_default' => true],
            ['title' => 'In progress'],
            ['title' => 'Resolved'],
            ['title' => 'Closed'],
            ['title' => 'Rejected'],
        ];

        foreach ($statuses as $key => $value) {
            TicketStatus::create($value);
        }
    }
}
