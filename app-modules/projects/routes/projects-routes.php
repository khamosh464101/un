<?php
use Modules\Projects\Http\Controllers\ProvinceController;
use Modules\Projects\Http\Controllers\DistrictController;
use Modules\Projects\Http\Controllers\GozarController;
use Modules\Projects\Http\Controllers\ProgramStatusController;
 use Modules\Projects\Http\Controllers\ProjectController;
 use Modules\Projects\Http\Controllers\PartnerController;
 use Modules\Projects\Http\Controllers\SubprojectTypeController;
 use Modules\Projects\Http\Controllers\SubprojectController;
 use Modules\Projects\Http\Controllers\ProjectStatusController;
 use Modules\Projects\Http\Controllers\StaffStatusController;
 use Modules\Projects\Http\Controllers\ProgramController;
 use Modules\Projects\Http\Controllers\StaffController;
 use Modules\Projects\Http\Controllers\DonorController;
 use Modules\Projects\Http\Controllers\ActivityStatusController;
 use Modules\Projects\Http\Controllers\ActivityTypeController;
 use Modules\Projects\Http\Controllers\TicketStatusController;
 use Modules\Projects\Http\Controllers\TicketTypeController;
 use Modules\Projects\Http\Controllers\TicketPriorityController;
 use Modules\Projects\Http\Controllers\ActivityController;
 use Modules\Projects\Http\Controllers\TicketController;
 use Modules\Projects\Http\Controllers\DocumentController;
 use Modules\Projects\Http\Controllers\TicketCommentController;
 use Modules\Projects\Http\Controllers\TicketHourController;
 use Modules\Projects\Http\Controllers\StaffContractTypeController;

 Route::middleware(['auth:sanctum', 'twofactor'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware(['auth:sanctum', 'twofactor'])->group(function () {
    // REFERENTIALS START
    Route::post('/api/provinces', [ProvinceController::class, 'index'])->middleware(['can:location view']);
    Route::get('/api/provinces/select2', [ProvinceController::class, 'select2']);
    Route::resource('/api/province', ProvinceController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/districts', [DistrictController::class, 'index'])->middleware(['can:location view']);
    Route::get('/api/districts/select2/{id?}', [DistrictController::class, 'select2']);
    Route::resource('/api/district', DistrictController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/gozars', [GozarController::class, 'index'])->middleware(['can:location view']);
    Route::get('/api/gozars/select2/{id?}', [GozarController::class, 'select2']);
    Route::resource('/api/gozar', GozarController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/projects-statuses', [ProjectStatusController::class, 'index'])->middleware(['can:project status view']);
    Route::get('/api/projects-statuses/select2', [ProjectStatusController::class, 'select2']);
    Route::resource('/api/projects-status', ProjectStatusController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/subproject-types', [SubprojectTypeController::class, 'index'])->middleware(['can:subproject type view']);
    Route::get('/api/subproject-types/select2', [SubprojectTypeController::class, 'select2']);
    Route::resource('/api/subproject-type', SubprojectTypeController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/staffs-statuses', [StaffStatusController::class, 'index'])->middleware(['can:staff status view']);
    Route::get('/api/staffs-statuses/select2', [StaffStatusController::class, 'select2']);
    Route::resource('/api/staffs-status', StaffStatusController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/staff-contract-types', [StaffContractTypeController::class, 'index'])->middleware(['can:staff contract type view']);
    Route::get('/api/staff-contract-types/select2', [StaffContractTypeController::class, 'select2']);
    Route::resource('/api/staff-contract-type', StaffContractTypeController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/activity-types', [ActivityTypeController::class, 'index'])->middleware(['can:activity type view']);
    Route::get('/api/activity-types/select2', [ActivityTypeController::class, 'select2']);
    Route::resource('/api/activity-type', ActivityTypeController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/activity-statuses', [ActivityStatusController::class, 'index'])->middleware(['can:activity status view']);
    Route::get('/api/activity-statuses/select2', [ActivityStatusController::class, 'select2']);
    Route::resource('/api/activity-status', ActivityStatusController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/ticket-statuses', [TicketStatusController::class, 'index'])->middleware(['can:task status view']);
    Route::get('/api/ticket-statuses/select2', [TicketStatusController::class, 'select2']);
    Route::resource('/api/ticket-status', TicketStatusController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/ticket-priorities', [TicketPriorityController::class, 'index'])->middleware(['can:task priority view']);
    Route::get('/api/ticket-priorities/select2', [TicketPriorityController::class, 'select2']);
    Route::resource('/api/ticket-priority', TicketPriorityController::class)->only(['store', 'edit', 'update', 'destroy']);
    // END REFERENTIALS

    // START PROJECT MANAGEMENT
    Route::post('/api/donors', [DonorController::class, 'index'])->middleware(['can:donor view']);
    Route::get('/api/donors/select2', [DonorController::class, 'select2']);
    Route::resource('/api/donor', DonorController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::get('/api/projects/select2', [ProjectController::class, 'select2']);
    Route::post('/api/projects', [ProjectController::class, 'index'])->middleware(['can:project view']);
    Route::post('/api/project/{project}', [ProjectController::class, 'update']);
    Route::post('/api/project/add/member', [ProjectController::class, 'addMember']);
    Route::post('/api/project/remove/member', [ProjectController::class, 'removeMember']);
    Route::post('/api/project/add/gozar', [ProjectController::class, 'addGozar']);
    Route::post('/api/project/edit/gozar', [ProjectController::class, 'editGozar']);
    Route::post('/api/project/remove/gozar', [ProjectController::class, 'removeGozar']);
    Route::post('/api/project/remove/district', [ProjectController::class, 'removeDistrict']);
    Route::resource('/api/project', ProjectController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::get('/api/partners/select2', [PartnerController::class, 'select2']);
    Route::post('/api/partners', [PartnerController::class, 'index'])->middleware(['can:partner view']);
    Route::resource('/api/partner', PartnerController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::get('/api/subprojects/select2', [SubprojectController::class, 'select2']);
    Route::post('/api/subprojects', [SubprojectController::class, 'index'])->middleware(['can:subproject view']);
    Route::post('/api/subproject/remove/district', [SubprojectController::class, 'removeDistrict']);
    Route::resource('/api/subproject', SubprojectController::class)->only(['store', 'edit', 'update', 'destroy']);
    Route::post('/api/subproject/add/gozar', [SubprojectController::class, 'addGozar']);
    Route::post('/api/subproject/edit/gozar', [SubprojectController::class, 'editGozar']);
    Route::post('/api/subproject/remove/gozar', [SubprojectController::class, 'removeGozar']);
  
    Route::get('/api/staffs/select2/{id?}', [StaffController::class, 'select2']);
    Route::post('/api/staffs', [StaffController::class, 'index'])->middleware(['can:staff view']);
    Route::post('/api/staff/{staff}', [StaffController::class, 'update']);
    Route::resource('/api/staff', StaffController::class)->only(['store', 'edit', 'update', 'destroy']);
   
    Route::post('/api/activities', [ActivityController::class, 'index'])->middleware(['can:activity view']);
    Route::get('/api/activities/locations/{id}', [ActivityController::class, 'getLocation']);
    Route::get('/api/activities/select2/{id?}', [ActivityController::class, 'select2']);
    Route::post('/api/activity/add/gozar', [ActivityController::class, 'addGozar']);
    Route::post('/api/activity/remove/gozar', [ActivityController::class, 'removeGozar']);
    Route::resource('/api/activity', ActivityController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::post('/api/tickets', [TicketController::class, 'index'])->middleware(['can:task view']);
    Route::post('/api/tickets/list', [TicketController::class, 'list']);
    Route::get('/api/tickets/locations/{id}', [TicketController::class, 'getLocation']);
    Route::post('/api/tickets/move', [TicketController::class, 'move'])->middleware(['can:task update']);
    Route::post('/api/tickets/reorder', [TicketController::class, 'reorder']);
    Route::get('/api/tickets/select2/{id?}', [TicketController::class, 'select2']);
    Route::post('/api/ticket/add/gozar', [TicketController::class, 'addGozar']);
    Route::post('/api/ticket/remove/gozar', [TicketController::class, 'removeGozar']);
    Route::resource('/api/ticket', TicketController::class)->only(['store', 'edit', 'update', 'destroy']);

    Route::resource('/api/ticket-comments', TicketCommentController::class)->only(['store', 'update', 'destroy']);

    // FILEPOND START
    Route::post('/api/document/upload', [DocumentController::class, 'process']);
    Route::delete('/api/document/remove/{id}', [DocumentController::class, 'remove']);  
    Route::get('/api/document/restore/{id}', [DocumentController::class, 'restore']); 
    Route::get('/api/document/load/{id}', [DocumentController::class, 'load']); 
    
    // FILEPOND END
    Route::resource('/api/document', DocumentController::class);
    // END PROJECT MANAGEMENT
});

Route::get('/api/document/download/{id}', [DocumentController::class, 'download']);
 

// Route::get('/projects/create', [ProjectsController::class, 'create'])->name('projects.create');
// Route::post('/projects', [ProjectsController::class, 'store'])->name('projects.store');
// Route::get('/projects/{project}', [ProjectsController::class, 'show'])->name('projects.show');
// Route::get('/projects/{project}/edit', [ProjectsController::class, 'edit'])->name('projects.edit');
// Route::put('/projects/{project}', [ProjectsController::class, 'update'])->name('projects.update');
// Route::delete('/projects/{project}', [ProjectsController::class, 'destroy'])->name('projects.destroy');
