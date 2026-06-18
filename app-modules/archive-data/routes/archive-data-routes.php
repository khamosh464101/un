<?php

// use Modules\ArchiveData\Http\Controllers\ArchiveDataController;

use Modules\ArchiveData\Http\Controllers\SyncKoboController;
use Modules\ArchiveData\Http\Controllers\SubmissionController;


Route::middleware(['auth:sanctum', 'twofactor'])->group(function () {
    Route::post('/archives/index', [SubmissionController::class, 'index']);
    // Route::post('/archives/add-as-beneficiary', [SubmissionController::class, 'addAsBeneficairy']);
    // Route::post('/archives/remove-as-beneficiary', [SubmissionController::class, 'removeAsBeneficairy']);
    Route::post('/archives/download-excel', [SubmissionController::class, 'downloadExcel']);
    Route::post('/archives/restore', [SubmissionController::class, 'restore']);
    Route::get('/archives/{id}/download-profile', [SubmissionController::class, 'downloadProfile']);
});