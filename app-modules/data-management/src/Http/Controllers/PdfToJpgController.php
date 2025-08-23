<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;

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
            
            foreach ($filesToProcess as $pdfFile) {
                try {
                    $filename = basename($pdfFile);
                    $result = $this->convertSinglePdf($filename);
                    
                    if (!isset($result['error'])) {
                        $successful++;
                    } else {
                        $failed++;
                    }
                } catch (Exception $e) {
                    $failed++;
                }
            }
            
            return response()->json([
                'message' => "Converted $successful PDF files to JPG" . ($failed > 0 ? " ($failed failed)" : ""),
                'successful' => $successful,
                'failed' => $failed
            ]);
            
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Convert single PDF to JPG
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
        
        // Download PDF to temporary location with proper filename
        $tempPdfPath = $tempDir . $baseName . '.pdf';
        file_put_contents($tempPdfPath, Storage::disk('gcs')->get($pdfPath));
        
        // Verify the file was created
        if (!file_exists($tempPdfPath)) {
            return ['error' => 'Failed to create temporary PDF file'];
        }
        
        // Check if Imagick is available
        if (extension_loaded('imagick')) {
            return $this->convertWithImagick($tempPdfPath, $pdfPath, $baseName, $tempDir);
        }
        
        // Check if Ghostscript is available
        if (!empty(shell_exec('which gs'))) {
            return $this->convertWithGhostscript($tempPdfPath, $pdfPath, $baseName, $tempDir);
        }
        
        return ['error' => 'No PDF conversion tool available'];
    }
    
    // Convert using Imagick
    protected function convertWithImagick($tempPdfPath, $pdfPath, $baseName, $tempDir)
    {
        $tempJpgPath = null;
        
        try {
            $imagick = new \Imagick();
            $imagick->setResolution(300);
            $imagick->readImage($tempPdfPath . '[0]');
            $imagick->setImageFormat('jpeg');
            
            $tempJpgPath = $tempDir . $baseName . '.jpg';
            $result = $imagick->writeImage($tempJpgPath);
            
            if (!$result) {
                throw new Exception("Failed to write JPG image");
            }
            
            // Upload JPG to storage
            $jpgPath = 'jpg/' . $baseName . '.jpg';
            Storage::disk('gcs')->put($jpgPath, file_get_contents($tempJpgPath));
            
            // Delete the original PDF
            Storage::disk('gcs')->delete($pdfPath);
            
            return [
                'message' => 'PDF converted to JPG successfully',
                'jpg_path' => $jpgPath
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
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
    
    // Convert using Ghostscript
    protected function convertWithGhostscript($tempPdfPath, $pdfPath, $baseName, $tempDir)
    {
        $tempJpgPath = null;
        
        try {
            $tempJpgPath = $tempDir . $baseName . '.jpg';
            
            // Use Ghostscript to convert PDF to JPG
            $command = "gs -dNOPAUSE -sDEVICE=jpeg -r150 -dFirstPage=1 -dLastPage=1 -sOutputFile='$tempJpgPath' '$tempPdfPath' -c quit 2>&1";
            $output = shell_exec($command);
            
            // Check if conversion was successful
            if (!file_exists($tempJpgPath) || filesize($tempJpgPath) === 0) {
                throw new Exception("Ghostscript conversion failed");
            }
            
            // Upload JPG to storage
            $jpgPath = 'jpg/' . $baseName . '.jpg';
            Storage::disk('gcs')->put($jpgPath, file_get_contents($tempJpgPath));
            
            // Delete the original PDF
            Storage::disk('gcs')->delete($pdfPath);
            
            return [
                'message' => 'PDF converted to JPG successfully',
                'jpg_path' => $jpgPath
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
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