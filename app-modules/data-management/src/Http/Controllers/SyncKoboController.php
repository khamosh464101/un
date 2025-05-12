<?php

namespace Modules\DataManagement\Http\Controllers;
use Modules\DataManagement\Services\KoboService;
use Illuminate\Http\Request;
use Modules\DataManagement\Models\Form;

class SyncKoboController
{
    protected $kobo;

    public function __construct(KoboService $kobo)
    {
        $this->kobo = $kobo;
    }

    public function listForms()
    {
        // $forms = $this->kobo->getForms();
        // return $forms;
        //  $forms = $this->kobo->getFormDetails();
        // Form::create(['raw_schema' => $forms]);
        return response()->json(Form::first());
        return $forms['asset'];
    }
    
}
