<?php

// use Modules\DataManagement\Http\Controllers\DataManagementController;
use Modules\DataManagement\Http\Controllers\SyncKoboController;
use Modules\DataManagement\Http\Controllers\SubmissionController;
use Modules\DataManagement\Http\Controllers\ExcelController;
use Modules\DataManagement\Http\Controllers\KoboController;
use Modules\DataManagement\Http\Controllers\SubmissionStatusController;
use Modules\DataManagement\Http\Controllers\PdfToJpgController;
Route::middleware(['auth:sanctum', 'twofactor'])->group(function () {
    Route::get('/data-management/get-form', [SubmissionController::class, 'getForm']);
    Route::post('/data-managements', [SyncKoboController::class, 'listForms'])->name('data-managements.index');
     Route::get('/data-management/get-open-projects', [SubmissionController::class, 'getOpenProjects']);
    Route::get('/data-management/get-sumbission', [SyncKoboController::class, 'getSubmission']);

    Route::post('/data-managements/submissions/index', [SubmissionController::class, 'index'])->middleware('can:profile view');
    Route::post('/data-managements/submissions/store', [SubmissionController::class, 'store'])->middleware(['can:profile create']);
    Route::post('/data-managements/submissions/update/{id}', [SubmissionController::class, 'update'])->middleware(['can:profile create']);
    Route::post('/data-managements/submissions/add-as-beneficiary', [SubmissionController::class, 'addAsBeneficairy'])->middleware(['can:add profile as beneficiary']);
    Route::post('/data-managements/submissions/remove-as-beneficiary', [SubmissionController::class, 'removeAsBeneficairy'])->middleware(['can:remove profile as beneficairy']);
    Route::post('/data-managements/submissions/download-excel', [SubmissionController::class, 'downloadExcel'])->middleware(['can:manage excel']);
    Route::post('/data-managements/submissions/import-excel', [SubmissionController::class, 'importExcel'])->middleware(['can:import excel']);
    Route::post('/data-managements/change-status', [SubmissionController::class, 'changeStatus'])->middleware('can:profile update');
    Route::post('/data-managements/submissions/move-to-archive', [SubmissionController::class, 'moveToArchive'])->middleware(['can:move profile to archive']);
    Route::post('/data-managements/submissions/add-photo', [SubmissionController::class, 'addPhoto'])->middleware(['can:profile create']);
    Route::post('/data-managements/submissions/remove-photo', [SubmissionController::class, 'removePhoto'])->middleware(['can:profile create']);
    Route::post('/data-managements/submissions/update-photo', [SubmissionController::class, 'updatePhoto'])->middleware(['can:profile create']);
    Route::delete('/data-managements/submissions/{id}', [SubmissionController::class, 'destroy'])->middleware(['can:profile delete']);


    Route::get('/data-managements/excels', [ExcelController::class, 'list'])->middleware(['can:manage excel']);
    Route::post('/data-managements/uplaod-excel', [ExcelController::class, 'uploadFile'])->middleware(['can:manage excel']);
    Route::get('/data-managements/excels/download/{filename}', [ExcelController::class, 'download'])->middleware(['can:manage excel']);
    Route::post('/data-managements/insert-excel', [ExcelController::class, 'insert'])->middleware(['can:manage excel']);
    Route::post('/data-managements/delete-excel', [ExcelController::class, 'delete'])->middleware(['can:manage excel']);

    Route::post('/submission-statuses', [SubmissionStatusController::class, 'index'])->middleware(['can:task status view']);
    Route::get('/submission-statuses/select2', [SubmissionStatusController::class, 'select2']);
    Route::resource('/submission-status', SubmissionStatusController::class)->only(['store', 'edit', 'update', 'destroy']);

});
// routes/api.php

Route::get('/storage/summary', [PdfToJpgController::class, 'getFolderSummary']);
Route::post('/storage/convert-batch', [PdfToJpgController::class, 'convertMultiplePdfsToJpg']);
Route::post('/storage/convert/{filename}', [PdfToJpgController::class, 'convertSinglePdf']);
Route::get('/storage/batch-progress/{batchId}', [PdfToJpgController::class, 'getBatchProgressApi']);

Route::get('/data-managements/submissions/edit/{id}', [SubmissionController::class, 'edit']);
Route::post('/data-managements/submissions/import-excel', [SubmissionController::class, 'importExcel']);
Route::get('/data-managements/add-form-to-db', [SyncKoboController::class, 'addFormToDb']);
Route::get('/data-managements/submissions/{id}/download-profile', [SubmissionController::class, 'downloadProfile']); //->middleware(['can:download profile']);





// Route::get('/data-managements/create', [DataManagementController::class, 'create'])->name('data-managements.create');
// Route::post('/data-managements', [DataManagementController::class, 'store'])->name('data-managements.store');
// Route::get('/data-managements/{data-management}', [DataManagementController::class, 'show'])->name('data-managements.show');
// Route::get('/data-managements/{data-management}/edit', [DataManagementController::class, 'edit'])->name('data-managements.edit');
// Route::put('/data-managements/{data-management}', [DataManagementController::class, 'update'])->name('data-managements.update');
// Route::delete('/data-managements/{data-management}', [DataManagementController::class, 'destroy'])->name('data-managements.destroy');
