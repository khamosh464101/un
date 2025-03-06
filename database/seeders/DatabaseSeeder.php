<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Database\Seeders\ProgramStatusSeeder;
use Modules\Projects\Database\Seeders\ProjectStatusSeeder;
use Modules\Projects\Database\Seeders\ProvinceSeeder;
use Modules\Projects\Database\Seeders\GozarSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([GozarSeeder::class]);
        // $this->call([ProvinceSeeder::class]);
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
