<?php

namespace Modules\Projects\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Models\TicketType;
class TicketTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['title' => 'Task', 'is_default' => true],
            ['title' => 'Bug'],
            ['title' => 'Error'],
        ];

        foreach ($types as $key => $value) {
            TicketType::create($value);
        }
    }
}
