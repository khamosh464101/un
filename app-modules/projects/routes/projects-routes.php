<?php
use Modules\Projects\Http\Controllers\ProvinceController;
use Modules\Projects\Http\Controllers\DistrictController;
use Modules\Projects\Http\Controllers\GozarController;
use Modules\Projects\Http\Controllers\ProgramStatusController;
 use Modules\Projects\Http\Controllers\ProjectController;
 use Modules\Projects\Http\Controllers\ProjectStatusController;
 use Modules\Projects\Http\Controllers\StaffStatusController;
 use Modules\Projects\Http\Controllers\ProgramController;
 use Modules\Projects\Http\Controllers\StaffController;
 use Modules\Projects\Http\Controllers\DonorController;
 use Modules\Projects\Http\Controllers\ActivityStatusController;
 use Modules\Projects\Http\Controllers\ActivityTypeController;
 use Modules\Projects\Http\Controllers\DocumentController;

 Route::middleware(['auth:sanctum', 'twofactor'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware(['auth:sanctum', 'twofactor'])->group(function () {
    Route::post('/api/provinces', [ProvinceController::class, 'index']);
    Route::get('/api/provinces/select2', [ProvinceController::class, 'select2']);
    Route::resource('/api/province', ProvinceController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/districts', [DistrictController::class, 'index']);
    Route::get('/api/districts/select2', [DistrictController::class, 'select2']);
    Route::resource('/api/district', DistrictController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/gozars', [GozarController::class, 'index']);
    Route::get('/api/gozars/select2', [GozarController::class, 'select2']);
    Route::resource('/api/gozar', GozarController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/programs-statuses', [ProgramStatusController::class, 'index']);
    Route::get('/api/programs-statuses/select2', [ProgramStatusController::class, 'select2']);
    Route::resource('/api/programs-status', ProgramStatusController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/programs', [ProgramController::class, 'index']);
    Route::get('/api/programs/select2', [ProgramController::class, 'select2']);
    Route::resource('/api/program', ProgramController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/donors', [DonorController::class, 'index']);
    Route::get('/api/donors/select2', [DonorController::class, 'select2']);
    Route::resource('/api/donor', DonorController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/projects-statuses', [ProjectStatusController::class, 'index']);
    Route::get('/api/projects-statuses/select2', [ProjectStatusController::class, 'select2']);
    Route::resource('/api/projects-status', ProjectStatusController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/projects', [ProjectController::class, 'index']);
    Route::resource('/api/project', ProjectController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/staffs-statuses', [StaffStatusController::class, 'index']);
    Route::get('/api/staffs-statuses/select2', [StaffStatusController::class, 'select2']);
    Route::resource('/api/staffs-status', StaffStatusController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::get('/api/staffs/select2', [StaffController::class, 'select2']);
    Route::post('/api/staffs', [StaffController::class, 'index']);
    Route::resource('/api/staff', StaffController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/activity-statuses', [ActivityStatusController::class, 'index']);
    Route::get('/api/activity-statuses/select2', [ActivityStatusController::class, 'select2']);
    Route::resource('/api/activity-status', ActivityStatusController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/activity-types', [ActivityTypeController::class, 'index']);
    Route::get('/api/activity-types/select2', [ActivityTypeController::class, 'select2']);
    Route::resource('/api/activity-type', ActivityTypeController::class)->only(['store', 'edit', 'update', 'destroy']);
    

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
