<?php

namespace Modules\Projects\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\Project;
use Modules\Projects\Models\Activity;
use Modules\Projects\Models\Province;
use Modules\Projects\Models\District;
use Modules\Projects\Models\Gozar;
use Modules\Projects\Http\Requests\TicketRequest;
use Carbon\Carbon;

class TicketController
{

    public function store(TicketRequest $request) {
        $data = $request->validated();
        $ticket = Ticket::create($data);
        return response()->json(['message' => 'Sucessfully added!', 'data' => $ticket], 201);
    }
}
