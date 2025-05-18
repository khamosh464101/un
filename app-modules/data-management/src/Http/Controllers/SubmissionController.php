<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Modules\DataManagement\Services\KoboService;
use Modules\DataManagement\Services\KoboSubmissionParser;
use Modules\DataManagement\Models\Form;

class SubmissionController
{
    public function getForm() {
        return response()->json(Form::first());
    }
}
