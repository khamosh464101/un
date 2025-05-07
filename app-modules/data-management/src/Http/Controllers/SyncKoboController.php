<?php

namespace Modules\DataManagement\Http\Controllers;
use App\Services\KoboService;
use Illuminate\Http\Request;

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
        $forms = $this->kobo->getFormDetails();
        return $forms['asset'];
    }
    
}
