<?php

// use Modules\DataManagement\Http\Controllers\DataManagementController;
use Modules\DataManagement\Http\Controllers\SyncKoboController;
use Modules\DataManagement\Http\Controllers\SubmissionController;

Route::get('/data-management/get-form', [SubmissionController::class, 'getForm']);
Route::get('/data-management/get-sumbission', [SyncKoboController::class, 'getSubmission']);
Route::get('/data-managements', [SyncKoboController::class, 'listForms'])->name('data-managements.index');
Route::get('/data-managements/add-form-to-db', [SyncKoboController::class, 'addFormToDb']);
Route::post('/data-managements/submissions/index', [SubmissionController::class, 'index']);
Route::post('/data-managements/submissions/store', [SubmissionController::class, 'store']);
Route::post('/data-managements/submissions/add-as-beneficiary', [SubmissionController::class, 'addAsBeneficairy']);
Route::post('/data-managements/submissions/remove-as-beneficiary', [SubmissionController::class, 'removeAsBeneficairy']);
Route::post('/data-managements/submissions/download-excel', [SubmissionController::class, 'downloadExcel']);
Route::post('/data-managements/submissions/import-excel', [SubmissionController::class, 'importExcel']);
Route::post('/data-managements/submissions/move-to-archive', [SubmissionController::class, 'moveToArchive']);
Route::get('/data-managements/submissions/{id}/download-profile', [SubmissionController::class, 'downloadProfile']);





// Route::get('/data-managements/create', [DataManagementController::class, 'create'])->name('data-managements.create');
// Route::post('/data-managements', [DataManagementController::class, 'store'])->name('data-managements.store');
// Route::get('/data-managements/{data-management}', [DataManagementController::class, 'show'])->name('data-managements.show');
// Route::get('/data-managements/{data-management}/edit', [DataManagementController::class, 'edit'])->name('data-managements.edit');
// Route::put('/data-managements/{data-management}', [DataManagementController::class, 'update'])->name('data-managements.update');
// Route::delete('/data-managements/{data-management}', [DataManagementController::class, 'destroy'])->name('data-managements.destroy');
