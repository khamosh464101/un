<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\SubmissionSheetImport;

class MultiTableImport implements WithMultipleSheets 
{
    public function sheets(): array
    {
        logger()->info("Error occured: ");
        return [
            0 => new SubmissionSheetImport(), // Use sheet name instead of index
            // 'Sheet2' => new AnotherSheetImport(),
        ];
    }
}