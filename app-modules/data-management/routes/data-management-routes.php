<?php

// use Modules\DataManagement\Http\Controllers\DataManagementController;
use Modules\DataManagement\Http\Controllers\SyncKoboController;
use Modules\DataManagement\Http\Controllers\SubmissionController;
use Modules\DataManagement\Http\Controllers\ExcelController;
use Modules\DataManagement\Http\Controllers\FormatController;
use Modules\DataManagement\Http\Controllers\MapController;
use Modules\DataManagement\Http\Controllers\KoboController;
use Modules\DataManagement\Http\Controllers\SubmissionStatusController;
use Modules\DataManagement\Http\Controllers\PdfToJpgController;
use Modules\DataManagement\Http\Controllers\LandUseController;
use Modules\DataManagement\Http\Controllers\ParcelController;
use Modules\DataManagement\Helpers\ModelHelper;
use Modules\DataManagement\Services\KoboService;
use Modules\DataManagement\Http\Controllers\LandController;
use Modules\DataManagement\Http\Controllers\ParcelMapController;
use Modules\DataManagement\Http\Controllers\ParcelSymbologyController;

use Modules\DataManagement\Http\Controllers\BulkDownloadController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;



Route::get('/fillables', function () {
    $fillables = ModelHelper::getFillableColumns();
    $service = new KoboService();
    $submission = $service->getSubmission(591669186);
    $cleaned = [];

    $othercolumns = [];

    foreach ($submission as $key => $value) {
        // Get the last part after the last slash
        $parts = explode('/', $key);
        $attributeName = end($parts);
        $cleaned[$attributeName] = $value;

        if (!in_array($attributeName, $fillables)) {
            $othercolumns[$attributeName] = $value;
        }
    }

    $arrays = [];
    $objects = [];
    $vars = [];

    foreach($othercolumns as $key => $value) {
        if (is_array($value)) {
            $arrays[$key] = $value;
        }
        if (is_string($value)) {
            $vars[$key] = $value;
        }
    }
    
    return $arrays;
    
});
Route::middleware(['auth:sanctum', 'twofactor'])->group(function () {
    Route::get('/data-management/{projectId}/get-form', [SubmissionController::class, 'getForm']);
    Route::get('/data-management/get-filterable', [SubmissionController::class, 'getFilterable']);
    Route::post('/data-managements', [SyncKoboController::class, 'listForms'])->name('data-managements.index')->middleware(['can:manage kobo import']);
    Route::get('/data-management/get-open-projects', [SubmissionController::class, 'getOpenProjects'])->middleware(['can:manage kobo import']);
    Route::get('/data-management/get-sumbission', [SyncKoboController::class, 'getSubmission'])->middleware(['can:manage kobo import']);

    Route::prefix('data-managements')->group(function () {
         // insert map data from excel to db
        Route::post('/insert-form-excel', [SyncKoboController::class, 'insertFormExcel'])->middleware(['can:manage excel']);
        Route::prefix('/submissions')->group(function () {
            Route::post('/index', [SubmissionController::class, 'index']);
            Route::post('/update/{id}', [SubmissionController::class, 'update']);
            Route::post('/add-as-beneficiary', [SubmissionController::class, 'addAsBeneficairy'])->middleware(['can:add profile as beneficiary']);
            Route::post('/remove-as-beneficiary', [SubmissionController::class, 'removeAsBeneficairy'])->middleware(['can:remove profile as beneficairy']);
            Route::post('/download-excel', [SubmissionController::class, 'downloadExcel'])->middleware(['can:manage excel']);
            Route::post('/import-excel', [SubmissionController::class, 'importExcel'])->middleware(['can:import excel']);
            Route::post('/change-status', [SubmissionController::class, 'changeStatus'])->middleware('can:profile update');
            Route::post('/move-to-archive', [SubmissionController::class, 'moveToArchive'])->middleware(['can:move profile to archive']);
            Route::post('/add-photo', [SubmissionController::class, 'addPhoto'])->middleware(['can:profile create']);
            Route::post('/remove-photo', [SubmissionController::class, 'removePhoto'])->middleware(['can:profile create']);
            Route::post('/update-photo', [SubmissionController::class, 'updatePhoto'])->middleware(['can:profile create']);
            Route::post('/delete-selected-records', [SubmissionController::class, 'deleteSelectedRecords'])->middleware(['can:profile delete']);
            Route::get('/{id}/download-profile', [SubmissionController::class, 'downloadProfile'])->middleware(['can:download profile']);
            
        });
        Route::resource('submissions', SubmissionController::class)->only(['store', 'destroy', 'edit']);
       

         

        Route::get('/excels', [ExcelController::class, 'list'])->middleware(['can:manage excel']);
        Route::post('/uplaod-excel', [ExcelController::class, 'uploadFile'])->middleware(['can:manage excel']);
        Route::get('/excels/download/{filename}', [ExcelController::class, 'download'])->middleware(['can:manage excel']);
        // insert all from excel to db
        Route::post('/insert-excel', [ExcelController::class, 'insert'])->middleware(['can:manage excel']);
        Route::post('/delete-excel', [ExcelController::class, 'delete'])->middleware(['can:manage excel']);
        
        // EXCEL FORMAT DEFINATION ROUTES
        Route::get('/formats-select2', [FormatController::class, 'select2'])->middleware(['can:manage excel']);
        Route::post('/formats', [FormatController::class, 'index'])->middleware(['can:manage excel']);
        Route::resource('/format', FormatController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->middleware(['can:manage excel']);
        Route::resource('/maps', MapController::class)->only(['store', 'update', 'destroy'])->middleware(['can:manage excel']);

        // PARCEL AND LANDUSE ROUTES
        Route::resource('/landuse-shapfiles', LandUseController::class)->only(['index', 'destroy'])->middleware('can:manage land-use');
        Route::get('/landuse-shapefile/download/{shapefile}', [LandUseController::class, 'download'])->middleware(['can:manage land-use']);
        Route::post('/landuse/upload', [LandUseController::class, 'uploadShapefile'])->middleware('can:manage land-use');
        Route::get('/landuse', [LandUseController::class, 'getLandUse'])->middleware('can:view land-use');
        Route::get('/symbology', [LandUseController::class, 'getSymbologySettings'])->middleware('can:view land-use');
        Route::post('/symbology/update', [LandUseController::class, 'updateSymbology'])->middleware('can:manage land-use');

        Route::post('/parcels/update-style', [ParcelController::class, 'updateStyle'])->middleware('can:manage parcel');
        Route::get('/parcels/style', [ParcelController::class, 'getStyle'])->middleware('can:manage parcel');
        
        Route::resource('/parcel/shapfiles', ParcelController::class)->only(['index', 'destroy'])->middleware('can:manage parcel');
        Route::get('/parcel/shapefile/images/{shapefile}/delete', [ParcelController::class, 'removeImages'])->middleware('can:manage parcel');
        Route::get('/parcel/shapefile/download/{shapefile}', [ParcelController::class, 'download'])->middleware(['can:manage parcel']);
        Route::post('/parcels/upload', [ParcelController::class, 'uploadShapefile'])->middleware('can:manage parcel');
        Route::post('/parcels', [ParcelController::class, 'getParcels'])->middleware('can:view parcel');
        Route::get('/parcels/{code}', [ParcelController::class, 'getParcel'])->middleware('can:manage parcel');
        Route::post('/parcels/link', [ParcelController::class, 'linkParcel'])->middleware('can:manage parcel');

        Route::prefix('/parcel-images')->group(function () {
            Route::post('initialize', [ParcelMapController::class, 'initialize']);
            Route::post('process-batch', [ParcelMapController::class, 'processBatch']);
            Route::get('progress', [ParcelMapController::class, 'getProgress']);
            Route::post('retry-failed', [ParcelMapController::class, 'retryFailed']);
        })->middleware('can:manage parcel');

        Route::post('/debug-shapefile', [LandUseController::class, 'debugShapefile']);

        Route::prefix('/bulk-download')->group(function () {
            Route::post('/zips', [BulkDownloadController::class, 'index']);
            Route::delete('/zips/{id}', [BulkDownloadController::class, 'destroy']);
            Route::post('/start', [BulkDownloadController::class, 'startBulkDownload']);
            Route::get('/progress/{batchId}', [BulkDownloadController::class, 'getProgress']);
            Route::get('/download/{batchId}', [BulkDownloadController::class, 'downloadBatch']);
            Route::get('/failed/{batchId}', [BulkDownloadController::class, 'getFailedItems']);
            Route::post('/retry/{batchId}', [BulkDownloadController::class, 'retryFailed']);
            Route::post('/cancel/{batchId}', [BulkDownloadController::class, 'cancelBatch']);
            Route::get('/batches', [BulkDownloadController::class, 'getBatchList']);
            Route::post('cleanup', [BulkDownloadController::class, 'cleanupOldBatches'])
                ->middleware('admin');
        })->middleware('can:download profile');

        Route::get('/parcel/symbology', [ParcelSymbologyController::class, 'index']);
        Route::get('/parcel/symbology/user', [ParcelSymbologyController::class, 'userQueries']);
        Route::get('/parcel/symbology/{id}', [ParcelSymbologyController::class, 'show']);
        Route::post('/parcel/symbology', [ParcelSymbologyController::class, 'store']);
        Route::put('/parcel/symbology/{id}', [ParcelSymbologyController::class, 'update']);
        Route::delete('/parcel/symbology/{id}', [ParcelSymbologyController::class, 'destroy']);
        Route::post('/parcel/symbology/{id}/execute', [ParcelSymbologyController::class, 'execute']);
    });
   
    
    

    Route::post('/submission-statuses', [SubmissionStatusController::class, 'index'])->middleware(['can:view submission status']);
    Route::get('/submission-statuses/select2', [SubmissionStatusController::class, 'select2']);
    Route::resource('/submission-status', SubmissionStatusController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::get('/storage/summary', [PdfToJpgController::class, 'getFolderSummary'])->middleware('can:manage location map');
    Route::post('/storage/convert-batch', [PdfToJpgController::class, 'convertMultiplePdfsToJpg'])->middleware('can:manage location map');


    





});



Route::get('/download-temp/{token}', function ($token) {
    $filePath = Cache::get('download_token:' . $token);
    
    if (!$filePath || !Storage::disk('public')->exists($filePath)) {
        abort(404, 'Download link expired or invalid');
    }
    
    // One-time use - remove token
    Cache::forget('download_token:' . $token);
    
    $filename = basename($filePath);
    
    return response()->download(
        Storage::disk('public')->path($filePath),
        $filename
    );
})->name('download.temp');
// routes/api.php








// Route::get('/data-managements/create', [DataManagementController::class, 'create'])->name('data-managements.create');
// Route::post('/data-managements', [DataManagementController::class, 'store'])->name('data-managements.store');
// Route::get('/data-managements/{data-management}', [DataManagementController::class, 'show'])->name('data-managements.show');
// Route::get('/data-managements/{data-management}/edit', [DataManagementController::class, 'edit'])->name('data-managements.edit');
// Route::put('/data-managements/{data-management}', [DataManagementController::class, 'update'])->name('data-managements.update');
// Route::delete('/data-managements/{data-management}', [DataManagementController::class, 'destroy'])->name('data-managements.destroy');
