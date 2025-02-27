<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Database\Seeders\ProgramStatusSeeder;
use Modules\Projects\Database\Seeders\ProjectStatusSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call([ProjectStatusSeeder::class]);
        // $this->call([ProgramStatusSeeder::class]);
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'azim@momtaz.af',
        //     'password' => bcrypt('azim12azim'),
        // ]);
    }
}
