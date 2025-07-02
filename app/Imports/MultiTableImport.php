<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\SubmissionSheetImport;


class MultiTableImport implements WithMultipleSheets 
{
    protected $startRow;
    protected $limit;

    public function __construct($startRow = 2, $limit = 5)
    {
        $this->startRow = $startRow;
        $this->limit = $limit;
    }
    public function sheets(): array
    {
        logger()->info("Error occured: $this->limit");
        return [
            0 => new SubmissionSheetImport($this->startRow, $this->limit), // Use sheet name instead of index
            // 'Sheet2' => new AnotherSheetImport(),
        ];
    }
}