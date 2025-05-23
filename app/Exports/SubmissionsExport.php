<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Modules\DataManagement\Models\Submission;

class SubmissionsExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Submission::all();
    }
}
