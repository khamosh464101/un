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
use Modules\DataManagement\Database\Seeders\SubmissionStatusSeeder;
use Modules\Projects\Database\Seeders\SubprojectTypeSeeder;
use Modules\Projects\Database\Seeders\StaffContractTypeSeeder;
use Modules\Projects\Models\Staff;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SettingSeeder;
use DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([SettingSeeder::class]);

        $this->call([ProvinceSeeder::class]);
        $this->call([ProjectStatusSeeder::class]);
        $this->call([ActivityStatusSeeder::class]);
        $this->call([ActivityTypeSeeder::class]);
        $this->call([StaffStatusSeeder::class]);
        $this->call([TicketStatusSeeder::class]);
        $this->call([TicketPrioritySeeder::class]);
        $this->call([StaffContractTypeSeeder::class]);
        $this->call([SubprojectTypeSeeder::class]);
        $this->call([SubmissionStatusSeeder::class]);
       $staff = Staff::create(
            [
                'name' => 'Azim Khamosh',
                'position_title' => 'Defult',
                'official_email' => 'azim@momtaz.af',
                'photo' => 'project-management/staff/photo/azim-khamosh-2025-03-09-16-52-22-149.jpg',
                'phone1' => '+93704499000',
                'duty_station' => 'Kabul',
                'staff_status_id' => 1
            ]
            );
        User::factory()->create([
            'name' => $staff->name,
            'email' => $staff->official_email,
            'phone' => $staff->phone1,
            'password' => bcrypt('azim12azim'),
            'staff_id' => $staff->id,
        ]);
        $this->call([PermissionSeeder::class]);
    }
}
