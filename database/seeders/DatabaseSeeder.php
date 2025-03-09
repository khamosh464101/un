<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Projects\Database\Seeders\ProgramStatusSeeder;
use Modules\Projects\Database\Seeders\ProjectStatusSeeder;
use Modules\Projects\Database\Seeders\ProvinceSeeder;
use Modules\Projects\Database\Seeders\GozarSeeder;
use Modules\Projects\Database\Seeders\ActivityStatusSeeder;
use Modules\Projects\Database\Seeders\ActivityTypeSeeder;
use Modules\Projects\Database\Seeders\StaffStatusSeeder;
use Modules\Projects\Database\Seeders\TicketPrioritySeeder;
use Modules\Projects\Database\Seeders\TicketStatusSeeder;
use Modules\Projects\Database\Seeders\TicketTypeSeeder;
use Database\Seeders\PermissionSeeder;
use DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // DB::table('roles')->truncate();
        // DB::table('permissions')->truncate();
        $this->call([PermissionSeeder::class]);
        // DB::table('provinces')->truncate();
        // DB::table('districts')->truncate();
        // $this->call([ProvinceSeeder::class]);
        // DB::table('gozars')->truncate();
        // $this->call([GozarSeeder::class]);
        // $this->call([ProgramStatusSeeder::class]);
        // $this->call([ProjectStatusSeeder::class]);
        // $this->call([ActivityStatusSeeder::class]);
        // $this->call([ActivityTypeSeeder::class]);
        // $this->call([StaffStatusSeeder::class]);
        //  $this->call([TicketStatusSeeder::class]);
        // $this->call([TicketPrioritySeeder::class]);
        // $this->call([TicketTypeSeeder::class]);
        
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'azim@momtaz.af',
        //     'password' => bcrypt('azim12azim'),
        // ]);
    }
}
