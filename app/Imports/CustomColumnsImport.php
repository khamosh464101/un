<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Importable;

class CustomColumnsImport implements 
    ToArray,
    WithHeadingRow, 
    WithLimit, 
    WithStartRow,
    WithMapping
{
    use Importable;
    
    protected $fixedColumns;
    protected $startRow;
    protected $numberOfRow;
    protected $columnIndices = [];

    public function __construct($startRow = 2, $numberOfRow = 100, $fixedColumns = [])
    {
        $this->startRow = $startRow;
        $this->numberOfRow = $numberOfRow;
        $this->fixedColumns = $fixedColumns;
        
        // Convert column letters to indices dynamically
        foreach ($fixedColumns as $columnLetter) {
            $this->columnIndices[$columnLetter] = $this->letterToIndex($columnLetter);
        }
    }

    /**
     * Convert column letter to zero-based index (A=0, B=1, etc.)
     */
    protected function letterToIndex($letter)
    {
        $letter = strtoupper($letter);
        $index = 0;
        $length = strlen($letter);
        
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($letter[$i]) - ord('A') + 1);
        }
        
        return $index - 1; // Convert to zero-based
    }

    public function startRow(): int
    {
        return $this->startRow;
    }

    public function limit(): int
    {
        return $this->numberOfRow;
    }

    public function map($row): array
    {
        logger()->info(array_keys($row));
        $mappedRow = [];
        
        
        // Get only the specific columns we want
        foreach ($this->fixedColumns as $colLetter) {
            $index = $this->columnIndices[$colLetter];
            
            // Try to get by index (numeric) or by column letter
            if (isset($row[$index])) {
                $mappedRow[$colLetter] = $row[$index];
            } else {
                $mappedRow[$colLetter] = $row[$colLetter] ?? null;
            }
        }
        
        return $mappedRow;
    }

    public function array(array $array)
    {
        return $array;
    }

    public function headingRow(): int
    {
        return 1; // headers are in row 1
    }
}