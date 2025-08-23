<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;
use Spatie\PdfToImage\Pdf;

class PdfToJpgController
{
    // Get folder summaries
    public function getFolderSummary()
    {
        try {
            $jpgFiles = Storage::disk('gcs')->files('jpg');
            $pdfFiles = Storage::disk('gcs')->files('pdf');
            
            return response()->json([
                'jpg_count' => count($jpgFiles),
                'pdf_count' => count($pdfFiles),
                'pdf_files' => $pdfFiles
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Convert multiple PDFs to JPG (10 at a time)
    public function convertMultiplePdfsToJpg(Request $request)
    {
        try {
            $pdfFiles = Storage::disk('gcs')->files('pdf');
            $jpgFiles = Storage::disk('gcs')->files('jpg');
            
            // Extract just the filenames without extension for comparison
            $existingJpgs = array_map(function($file) {
                return pathinfo($file, PATHINFO_FILENAME);
            }, $jpgFiles);
            
            // Filter out PDFs that already have JPG counterparts
            $filesToProcess = [];
            foreach ($pdfFiles as $pdfFile) {
                $pdfName = pathinfo($pdfFile, PATHINFO_FILENAME);
                if (!in_array($pdfName, $existingJpgs)) {
                    $filesToProcess[] = $pdfFile;
                }
            }
            
            // Limit to 10 files
            $filesToProcess = array_slice($filesToProcess, 0, 10);
            $totalFiles = count($filesToProcess);
            
            if ($totalFiles === 0) {
                return response()->json([
                    'message' => 'No PDF files found to convert (all PDFs already have JPG counterparts)'
                ]);
            }
            
            $successful = 0;
            $failed = 0;
            $failedFiles = [];
            
            foreach ($filesToProcess as $pdfFile) {
                try {
                    $filename = basename($pdfFile);
                    $result = $this->convertSinglePdf($filename);
                    
                    if (!isset($result['error'])) {
                        $successful++;
                    } else {
                        $failed++;
                        $failedFiles[] = [
                            'file' => $filename,
                            'error' => $result['error']
                        ];
                    }
                } catch (Exception $e) {
                    $failed++;
                    $failedFiles[] = [
                        'file' => basename($pdfFile),
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return response()->json([
                'message' => "Converted $successful PDF files to JPG" . ($failed > 0 ? " ($failed failed)" : ""),
                'successful' => $successful,
                'failed' => $failed,
                'failed_files' => $failedFiles
            ]);
            
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Convert single PDF to JPG using Spatie package
    protected function convertSinglePdf($filename)
    {
        // Remove .pdf extension if present
        $baseName = str_replace('.pdf', '', $filename);
        $pdfPath = 'pdf/' . $baseName . '.pdf';
        $jpgPath = 'jpg/' . $baseName . '.jpg';
        
        // Check if PDF exists
        if (!Storage::disk('gcs')->exists($pdfPath)) {
            return ['error' => 'PDF file not found'];
        }
        
        // Check if JPG already exists
        if (Storage::disk('gcs')->exists($jpgPath)) {
            return ['error' => 'JPG already exists for this PDF'];
        }
        
        // Create temporary directory if it doesn't exist
        $tempDir = sys_get_temp_dir() . '/pdf_conversion/';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Download PDF to temporary location
        $tempPdfPath = $tempDir . $baseName . '.pdf';
        file_put_contents($tempPdfPath, Storage::disk('gcs')->get($pdfPath));
        
        // Verify the file was created
        if (!file_exists($tempPdfPath)) {
            return ['error' => 'Failed to create temporary PDF file'];
        }
        
        $tempJpgPath = null;
        
        try {
            // Use Spatie PDF to Image package
            $pdf = new Pdf($tempPdfPath);
            
            // Set output file path
            $tempJpgPath = $tempDir . $baseName . '.jpg';
            // Set resolution (DPI)
            // $pdf->setResolution(300);
            // $pdf->setResolution(300);
            
            // Save the first page as JPG
            $pdf->saveImage($tempJpgPath);
            
            // Verify the JPG was created
            if (!file_exists($tempJpgPath) || filesize($tempJpgPath) === 0) {
                throw new Exception("Failed to create JPG image");
            }
            
            // Upload JPG to storage
            Storage::disk('gcs')->put($jpgPath, file_get_contents($tempJpgPath));
            
            // Delete the original PDF (optional - you might want to keep it)
            Storage::disk('gcs')->delete($pdfPath);
            
            return [
                'message' => 'PDF converted to JPG successfully',
                'jpg_path' => $jpgPath,
                'pdf_deleted' => true
            ];
            
        } catch (Exception $e) {
            return ['error' => 'PDF conversion failed: ' . $e->getMessage()];
        } finally {
            // Clean up temporary files
            if ($tempJpgPath && file_exists($tempJpgPath)) {
                unlink($tempJpgPath);
            }
            if (file_exists($tempPdfPath)) {
                unlink($tempPdfPath);
            }
        }
    }
}