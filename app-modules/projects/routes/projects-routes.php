<?php

use Modules\Projects\Http\Controllers\ProgramStatusController;
 use Modules\Projects\Http\Controllers\ProjectController;
 use Modules\Projects\Http\Controllers\ProjectStatusController;
 use Modules\Projects\Http\Controllers\ProgramController;
 use Modules\Projects\Http\Controllers\DonorController;
 use Modules\Projects\Http\Controllers\DocumentController;

 Route::middleware(['auth:sanctum', 'twofactor'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware(['auth:sanctum', 'twofactor'])->group(function () {
    
    Route::resource('/api/programs-status', ProgramStatusController::class)->only(['index','store', 'update']);
    Route::post('/api/programs', [ProgramController::class, 'index']);
    Route::get('/api/programs/select2', [ProgramController::class, 'select2']);
    Route::resource('/api/program', ProgramController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/donors', [DonorController::class, 'index']);
    Route::get('/api/donors/select2', [DonorController::class, 'select2']);
    Route::resource('/api/donor', DonorController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::resource('/api/projects-status', ProjectStatusController::class)->only(['index','store', 'update']);
    Route::post('/api/projects', [ProjectController::class, 'index']);
    Route::resource('/api/project', ProjectController::class)->only(['store', 'edit', 'update', 'destroy']);

    // FILEPOND START
    Route::post('/api/document/upload', [DocumentController::class, 'process']);
    Route::delete('/api/document/remove/{id}', [DocumentController::class, 'remove']);  
    Route::get('/api/document/restore/{id}', [DocumentController::class, 'restore']); 
    Route::get('/api/document/load/{id}', [DocumentController::class, 'load']); 
    // FILEPOND END
    Route::resource('/api/document', DocumentController::class);
});
 

// Route::get('/projects/create', [ProjectsController::class, 'create'])->name('projects.create');
// Route::post('/projects', [ProjectsController::class, 'store'])->name('projects.store');
// Route::get('/projects/{project}', [ProjectsController::class, 'show'])->name('projects.show');
// Route::get('/projects/{project}/edit', [ProjectsController::class, 'edit'])->name('projects.edit');
// Route::put('/projects/{project}', [ProjectsController::class, 'update'])->name('projects.update');
// Route::delete('/projects/{project}', [ProjectsController::class, 'destroy'])->name('projects.destroy');
