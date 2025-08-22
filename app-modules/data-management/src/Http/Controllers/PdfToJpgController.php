<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Log;

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
                'pdf_files' => $pdfFiles,
                'jpg_files' => $jpgFiles, // Also return JPG files to check for existing ones
                'imagick_available' => extension_loaded('imagick'),
                'ghostscript_available' => !empty(shell_exec('which gs'))
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Convert multiple PDFs to JPG (100 at a time)
    public function convertMultiplePdfsToJpg(Request $request)
    {
        try {
            $batchId = uniqid(); // Unique ID for this batch
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
            
            // Limit to 100 files or all files if less than 100
            $filesToProcess = array_slice($filesToProcess, 0, 10);
            $totalFiles = count($filesToProcess);
            
            if ($totalFiles === 0) {
                return response()->json([
                    'message' => 'No PDF files found to convert (all PDFs already have JPG counterparts)',
                    'batch_id' => $batchId,
                    'skipped' => count($pdfFiles)
                ]);
            }
            
            // Store batch information for progress tracking
            $this->storeBatchProgress($batchId, [
                'total' => $totalFiles,
                'processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'skipped' => count($pdfFiles) - $totalFiles,
                'current_file' => '',
                'status' => 'processing'
            ]);
            
            // Process files in background
            $this->processBatch($batchId, $filesToProcess);
            
            return response()->json([
                'message' => 'Started converting ' . $totalFiles . ' PDF files',
                'batch_id' => $batchId,
                'total_files' => $totalFiles,
                'skipped' => count($pdfFiles) - $totalFiles
            ]);
            
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Process a batch of PDF files
    protected function processBatch($batchId, $filesToProcess)
    {
        $successful = 0;
        $failed = 0;
        $jpgFiles = Storage::disk('gcs')->files('jpg');
        $existingJpgs = array_map(function($file) {
            return pathinfo($file, PATHINFO_FILENAME);
        }, $jpgFiles);
        
        foreach ($filesToProcess as $index => $pdfFile) {
            $filename = basename($pdfFile);
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            
            // Check again if JPG already exists (in case it was created during batch processing)
            if (in_array($baseName, $existingJpgs)) {
                // Skip this file as JPG already exists
                $this->updateBatchProgress($batchId, [
                    'processed' => $index + 1,
                    'skipped' => $this->getBatchProgress($batchId)['skipped'] + 1,
                    'current_file' => $filename,
                    'status' => 'processing'
                ]);
                continue;
            }
            
            // Update progress
            $this->updateBatchProgress($batchId, [
                'processed' => $index + 1,
                'current_file' => $filename,
                'status' => 'processing'
            ]);
            
            try {
                // Convert single PDF
                $result = $this->convertSinglePdf($filename);
                
                if (isset($result['error'])) {
                    $failed++;
                    // Log error but continue with next file
                    Log::error("Failed to convert $filename: " . $result['error']);
                } else {
                    $successful++;
                    // Add to existing JPGs to prevent duplicate processing
                    $existingJpgs[] = $baseName;
                }
                
                // Update success/failure counts
                $this->updateBatchProgress($batchId, [
                    'successful' => $successful,
                    'failed' => $failed
                ]);
                
            } catch (Exception $e) {
                $failed++;
                $this->updateBatchProgress($batchId, [
                    'failed' => $failed
                ]);
                Log::error("Failed to convert $filename: " . $e->getMessage());
            }
            
            // Small delay to prevent server overload
            usleep(100000); // 0.1 second
        }
        
        // Mark batch as completed
        $this->updateBatchProgress($batchId, [
            'status' => 'completed'
        ]);
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
    
    // Store batch progress (using file-based storage)
    protected function storeBatchProgress($batchId, $data)
    {
        $storagePath = storage_path('app/batch_progress/');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        file_put_contents($storagePath . $batchId . '.json', json_encode($data));
    }
    
    // Update batch progress
    protected function updateBatchProgress($batchId, $updates)
    {
        $storagePath = storage_path('app/batch_progress/');
        $filePath = $storagePath . $batchId . '.json';
        
        if (file_exists($filePath)) {
            $currentData = json_decode(file_get_contents($filePath), true);
            $updatedData = array_merge($currentData, $updates);
            file_put_contents($filePath, json_encode($updatedData));
        }
    }
    
    // Get batch progress data
    protected function getBatchProgress($batchId)
    {
        $storagePath = storage_path('app/batch_progress/');
        $filePath = $storagePath . $batchId . '.json';
        
        if (file_exists($filePath)) {
            return json_decode(file_get_contents($filePath), true);
        }
        
        return [];
    }
    
    // Get batch progress via API
    public function getBatchProgressApi($batchId)
    {
        $storagePath = storage_path('app/batch_progress/');
        $filePath = $storagePath . $batchId . '.json';
        
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            return response()->json($data);
        }
        
        return response()->json(['error' => 'Batch not found'], 404);
    }
    
    // Convert using Imagick
    protected function convertWithImagick($tempPdfPath, $pdfPath, $baseName, $tempDir)
    {
        $tempJpgPath = null;
        
        try {
            // Verify the PDF file exists and is readable
            if (!file_exists($tempPdfPath) || !is_readable($tempPdfPath)) {
                throw new Exception("Temporary PDF file not accessible: " . $tempPdfPath);
            }
            
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($tempPdfPath . '[0]');
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90);
            
            $tempJpgPath = $tempDir . $baseName . '.jpg';
            $result = $imagick->writeImage($tempJpgPath);
            
            if (!$result) {
                throw new Exception("Failed to write JPG image");
            }
            
            // Verify the JPG was created
            if (!file_exists($tempJpgPath)) {
                throw new Exception("Temporary JPG file was not created");
            }
            
            // Upload JPG to storage
            $jpgPath = 'jpg/' . $baseName . '.jpg';
            Storage::disk('gcs')->put($jpgPath, file_get_contents($tempJpgPath));
            
            // Delete the original PDF
            Storage::disk('gcs')->delete($pdfPath);
            
            return [
                'message' => 'PDF converted to JPG successfully',
                'jpg_path' => $jpgPath,
                'method' => 'imagick'
            ];
        } catch (Exception $e) {
            // Clean up temporary files
            if ($tempJpgPath && file_exists($tempJpgPath)) {
                unlink($tempJpgPath);
            }
            throw new Exception("Imagick conversion failed: " . $e->getMessage());
        } finally {
            // Always clean up PDF file
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
            // Verify the PDF file exists and is readable
            if (!file_exists($tempPdfPath) || !is_readable($tempPdfPath)) {
                throw new Exception("Temporary PDF file not accessible: " . $tempPdfPath);
            }
            
            $tempJpgPath = $tempDir . $baseName . '.jpg';
            
            // Use Ghostscript to convert PDF to JPG
            $command = "gs -dNOPAUSE -sDEVICE=jpeg -r150 -dFirstPage=1 -dLastPage=1 -sOutputFile='$tempJpgPath' '$tempPdfPath' -c quit 2>&1";
            $output = shell_exec($command);
            
            // Check if conversion was successful
            if (!file_exists($tempJpgPath) || filesize($tempJpgPath) === 0) {
                throw new Exception("Ghostscript conversion failed. Output: " . $output);
            }
            
            // Upload JPG to storage
            $jpgPath = 'jpg/' . $baseName . '.jpg';
            Storage::disk('gcs')->put($jpgPath, file_get_contents($tempJpgPath));
            
            // Delete the original PDF
            Storage::disk('gcs')->delete($pdfPath);
            
            return [
                'message' => 'PDF converted to JPG successfully',
                'jpg_path' => $jpgPath,
                'method' => 'ghostscript'
            ];
        } catch (Exception $e) {
            // Clean up temporary files
            if ($tempJpgPath && file_exists($tempJpgPath)) {
                unlink($tempJpgPath);
            }
            throw new Exception("Ghostscript conversion failed: " . $e->getMessage());
        } finally {
            // Always clean up PDF file
            if (file_exists($tempPdfPath)) {
                unlink($tempPdfPath);
            }
        }
    }
}