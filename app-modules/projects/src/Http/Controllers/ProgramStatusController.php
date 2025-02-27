<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\ProgramStatus;

class ProgramStatusController
{
    public function index() {
        return response()->json(ProgramStatus::all(), 201);
    }
}
