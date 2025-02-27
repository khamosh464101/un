<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\ProjectStatus;

class ProjectStatusController
{
    public function index() {
        return response()->json(ProjectStatus::all(), 201);
    }
}
