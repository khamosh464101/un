<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'kobo_base_url', 
                'value' => 'https://eu.kobotoolbox.org/api/v2', 
                'label' =>  'Kobo toolbox API URL',
                'type' => 'text' 
            ],
            [
                'key' => 'kobo_token', 
                'value' => '37505e43c62161e95c146151ad8b09810e6d3454', 
                'label' =>  'Kobo toolbox API token',
                'type' => 'text'
            ],
            [
                'key' => 'kobo_form_id', 
                'value' => 'a6XCaWhJBrFKWpB5wFuvMU', 
                'label' =>  'Kobo toolbox API form ID for submissions',
                'type' => 'text'
            ],
            [
                'key' => 'kobo_copy_form_id', 
                'value' => 'a36vAYGb2v3rvengRLFM7o',
                'label' =>  'Kobo toolbox API form ID for structure', 
                'type' => 'text'
            ],
            [
                'key' => 'firebase_admin_credentials', 
                'value' => 'settings/firebase-auth.json', 
                'label' =>  'Firebase admin credentials for backend', 
                'type' => 'file'
            ],
            [
                'key' => 'google_cloud_project_id', 
                'value' => 'un-project-460907',
                'label' =>  'Google cloude project ID for vision AI',  
                'type' => 'text'
            ],
            [
                'key' => 'google_application_credentials', 
                'value' => 'settings/un-project-460907-d91021c78057.json', 
                'label' =>  'Google applicatin credentials fir Vision AI',
                'type' => 'file'
            ],
        ];

        foreach ($settings as $key => $value) {
            Setting::create($value);
        }
    }

}
