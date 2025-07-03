<?php

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SplitExcelFile extends Command
{
    protected $signature = 'split:excel {file}';
    protected $description = 'Split a large Excel file into 3 parts';

    public function handle()
    {
        $filePath = $this->argument('file'); // storage_path('app/private/excel/wochtangi_final.xlsx');
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $totalRows = $sheet->getHighestDataRow();
        $rowsPerFile = ceil($totalRows / 3); // Split into ~3 equal parts

        // Split into 3 files
        for ($i = 0; $i < 3; $i++) {
            $newSpreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $newSheet = $newSpreadsheet->getActiveSheet();

            // Copy headers (assumes row 1 is headers)
            $newSheet->fromArray(
                $sheet->rangeToArray('A1:' . $sheet->getHighestDataColumn() . '1')
            );

            // Calculate start/end rows for this chunk
            $startRow = ($i * $rowsPerFile) + 2; // Skip header
            $endRow = min($startRow + $rowsPerFile - 1, $totalRows);

            // Copy data rows
            $rowData = $sheet->rangeToArray("A{$startRow}:" . $sheet->getHighestDataColumn() . "{$endRow}");
            $newSheet->fromArray($rowData, null, 'A2');

            // Save to new file
            $writer = IOFactory::createWriter($newSpreadsheet, 'Xlsx');
            $outputPath = storage_path("app/split_part_" . ($i + 1) . ".xlsx");
            $writer->save($outputPath);

            $this->info("Created: " . basename($outputPath));
        }

        $this->info("Splitting complete!");
    }
}
