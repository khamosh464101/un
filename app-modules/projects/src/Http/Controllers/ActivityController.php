<?php

namespace Modules\Projects\Http\Controllers;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Activity;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Http\Requests\ActivityRequest;
use Modules\Projects\Http\Controllers\ProgramController;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityController
{
    

    public function store(ActivityRequest $request) {
        $data = $request->validated();
        $activity = activity::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $activity], 201);
    }
}
