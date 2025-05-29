<?php

// use Modules\ArchiveData\Http\Controllers\ArchiveDataController;

use Modules\ArchiveData\Http\Controllers\SyncKoboController;
use Modules\ArchiveData\Http\Controllers\SubmissionController;


Route::post('/archives/index', [SubmissionController::class, 'index']);
// Route::post('/archives/add-as-beneficiary', [SubmissionController::class, 'addAsBeneficairy']);
// Route::post('/archives/remove-as-beneficiary', [SubmissionController::class, 'removeAsBeneficairy']);
Route::post('/archives/download-excel', [SubmissionController::class, 'downloadExcel']);
Route::post('/archives/restore', [SubmissionController::class, 'restore']);

Route::get('/archives/{id}/download-profile', [SubmissionController::class, 'downloadProfile']);
// Route::get('/archive-datas', [ArchiveDataController::class, 'index'])->name('archive-datas.index');
// Route::get('/archive-datas/create', [ArchiveDataController::class, 'create'])->name('archive-datas.create');
// Route::post('/archive-datas', [ArchiveDataController::class, 'store'])->name('archive-datas.store');
// Route::get('/archive-datas/{archive-datum}', [ArchiveDataController::class, 'show'])->name('archive-datas.show');
// Route::get('/archive-datas/{archive-datum}/edit', [ArchiveDataController::class, 'edit'])->name('archive-datas.edit');
// Route::put('/archive-datas/{archive-datum}', [ArchiveDataController::class, 'update'])->name('archive-datas.update');
// Route::delete('/archive-datas/{archive-datum}', [ArchiveDataController::class, 'destroy'])->name('archive-datas.destroy');
