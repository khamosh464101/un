<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\SubmissionSheetImport;


class MultiTableImport implements WithMultipleSheets 
{
    protected $startRow;
    protected $limit;
    protected $chunkSize;
    protected $projectId;

    public function __construct($startRow = 2, $limit = 100, $chunkSize = 5, $projectId)
    {
        $this->startRow = $startRow;
        $this->limit = $limit;
        $this->chunkSize = $chunkSize;
        $this->projectId = $projectId;
    }

    public function sheets(): array
    {
        return [
            0 => (new SubmissionSheetImport($this->startRow, $this->limit, $this->projectId))
                ->setChunkSize($this->chunkSize),
        ];
    }
}