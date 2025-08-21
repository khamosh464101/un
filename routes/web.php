<?php

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use Google\Cloud\Storage\StorageClient;





Route::get('/test', [TestController::class, 'index']);
Route::get('/', function () {
    Storage::disk('gcs')->put('folder/file_second.txt', 'Contents here...');
// return $storage = new StorageClient([
//         'keyFilePath' => env('GOOGLE_CLOUD_KEY_FILE'),
//     ]);

//     $buckets = iterator_to_array($storage->buckets(['project' => 'un-project-462414']));
//     return $buckets;




// Check if a file exists
// Storage::disk('gcs')->exists('avatars/1.png');

// // Get the public URL
// $url = Storage::disk('gcs')->url('avatars/1.png');

// // Generate a temporary signed URL
// $temporaryUrl = Storage::disk('gcs')->temporaryUrl('avatars/1.png', now()->addMinutes(30));

// // Change visibility
// Storage::disk('gcs')->setVisibility('avatars/1.png', 'public');
    // $role = Role::create(['name' => 'writer']);
    // $permission = Permission::create(['name' => 'edit articles']);
    // return $role;
    // $filePath = storage_path('app/data/villages.json');
    // $jsonData = file_get_contents($filePath);
    // $dataArray = json_decode($jsonData, true);
    // foreach($dataArray as $key => $p) {
    //     echo $key;
    //     echo $p['name']. '<br/>';
    // }

    //  dd(count($dataArray));
    return ['Laravel' => app()->version()];
});

require __DIR__.'/auth.php';
