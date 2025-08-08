<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class SendEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-emails
    {--excel=Excels/Daman.xlsx : Excel filename in public folder} 
    {--pdf-dir=public/gis/Daman : PDF directory}';

    protected $description = 'Check which survey records are missing PDF files with generated filenames';


        public function handle()
    {
        $excelFile = public_path($this->option('excel'));
        $pdfDirectory = base_path($this->option('pdf-dir'));
        
        // Validate inputs
        if (!file_exists($excelFile)) {
            $this->error("Excel file not found at: {$excelFile}");
            return 1;
        }
        
        if (!is_dir($pdfDirectory)) {
            $this->error("PDF directory not found: {$pdfDirectory}");
            return 1;
        }
        
        $this->info("Processing survey records and checking PDF files...");
        
        $missingPdfs = [];
        $totalRecords = 0;
        
        // Process Excel file
        $data = Excel::toArray(null, $excelFile);
        $rows = $data[0]; // Get first sheet
        
        // Get header row (assuming first row contains headers)
        $headers = array_shift($rows);
        
        foreach ($rows as $index => $row) {
            $totalRecords++;
            $recordNumber = $index + 2; // +2 because we removed header row
            
            try {
                // Log raw row data for debugging
                Log::info('Processing row:', ['row' => $row, 'headers' => $headers]);
                
                // Find column positions (case-insensitive)
                $provincePos = $this->findColumnPosition($headers, 'Province Code');
                $cityPos = $this->findColumnPosition($headers, 'City Code');
                $districtPos = $this->findColumnPosition($headers, 'District Code');
                $guzarPos = $this->findColumnPosition($headers, '1.6 Guzar Code Number');
                $blockPos = $this->findColumnPosition($headers, '1.7 Block Code Number');
                $propertyPos = $this->findColumnPosition($headers, '1.8 Property / House Code Number');
                
                // Get values from their positions
                $province = $this->formatCode($row[$provincePos] ?? '');
                $city = $this->formatCode($row[$cityPos] ?? '');
                $district = $this->formatCode($row[$districtPos] ?? '');
                $guzar = $this->formatCode($row[$guzarPos] ?? '');
                $block = $this->ensureThreeDigits($row[$blockPos] ?? '');
                $property = $this->ensureThreeDigits($row[$propertyPos] ?? '');
                
                // Construct PDF filename
                $pdfFilename = sprintf(
                    'D_%s-%s-%s-%s-%s-%s.pdf',
                    $province,
                    $city,
                    $district,
                    $guzar,
                    $block,
                    $property
                );
                
                $pdfPath = $pdfDirectory . '/' . $pdfFilename;
                
                if (!file_exists($pdfPath)) {
                    $missingPdfs[] = [
                        'record_id' => $recordNumber,
                        'expected_pdf' => $pdfFilename,
                        'reason' => 'PDF file not found',
                        'components' => [
                            'province' => $province,
                            'city' => $city,
                            'district' => $district,
                            'guzar' => $guzar,
                            'block' => $block,
                            'property' => $property
                        ],
                        'raw_data' => $row
                    ];
                }
            } catch (\Exception $e) {
                $missingPdfs[] = [
                    'record_id' => $recordNumber,
                    'reason' => 'Error processing record: ' . $e->getMessage(),
                    'raw_data' => $row
                ];
            }
        }
        
        // Display results
        $this->info("\nResults:");
        $this->info("Total records processed: {$totalRecords}");
        $this->info("Records with missing PDFs: " . count($missingPdfs));
        
        // Generate report
        $this->generateReport($missingPdfs, $headers);
        
        return 0;
    }
    
    /**
     * Find column position by name (case-insensitive)
     */
    protected function findColumnPosition(array $headers, string $search): int
    {
        $searchLower = strtolower(trim($search));
        
        foreach ($headers as $index => $header) {
            if (strtolower(trim($header)) === $searchLower) {
                return $index;
            }
        }
        
        throw new \Exception("Column '{$search}' not found in headers");
    }
    
    protected function formatCode($value)
    {
        return str_pad(trim($value), 2, '0', STR_PAD_LEFT);
    }
    
    protected function ensureThreeDigits($value)
    {
        return str_pad(trim($value), 3, '0', STR_PAD_LEFT);
    }
    
    protected function generateReport($missingPdfs, $headers)
    {
        $reportFilename = 'missing_survey_pdfs_' . date('Ymd_His') . '.csv';
        $reportPath = storage_path('app/' . $reportFilename);
        
        $file = fopen($reportPath, 'w');
        
        // Write headers
        fputcsv($file, array_merge([
            'Record ID',
            'Expected PDF',
            'Province Code',
            'City Code',
            'District Code',
            'Guzar Code',
            'Block Code',
            'Property Code',
            'Reason'
        ], $headers));
        
        // Write data
        foreach ($missingPdfs as $row) {
            fputcsv($file, array_merge([
                $row['record_id'],
                $row['expected_pdf'] ?? '',
                $row['components']['province'] ?? '',
                $row['components']['city'] ?? '',
                $row['components']['district'] ?? '',
                $row['components']['guzar'] ?? '',
                $row['components']['block'] ?? '',
                $row['components']['property'] ?? '',
                $row['reason']
            ], $row['raw_data']));
        }
        
        fclose($file);
        
        $this->info("\nDetailed report generated at: " . storage_path('app/' . $reportFilename));
        $this->info("The report includes all original columns for reference.");
    }
}
