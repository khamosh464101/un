<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\SubmissionSheetImport;


class MultiTableImport implements WithMultipleSheets 
{
    protected $startRow;
    protected $limit;
    protected $chunkSize;

    public function __construct($startRow = 2, $limit = 100, $chunkSize = 100)
    {
        $this->startRow = $startRow;
        $this->limit = $limit;
        $this->chunkSize = $chunkSize;
    }

    public function sheets(): array
    {
        return [
            0 => (new SubmissionSheetImport($this->startRow, $this->limit))
                ->setChunkSize($this->chunkSize),
        ];
    }
}