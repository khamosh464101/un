<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Redis; // or use cache for progress tracking

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
            
            // Limit to 100 files or all files if less than 100
            $filesToProcess = array_slice($pdfFiles, 0, 100);
            $totalFiles = count($filesToProcess);
            
            if ($totalFiles === 0) {
                return response()->json([
                    'message' => 'No PDF files found to convert',
                    'batch_id' => $batchId
                ]);
            }
            
            // Store batch information for progress tracking
            $this->storeBatchProgress($batchId, [
                'total' => $totalFiles,
                'processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'current_file' => '',
                'status' => 'processing'
            ]);
            
            // Process files in background (you might want to use queues for production)
            // For simplicity, we'll process synchronously but you should use queues
            $this->processBatch($batchId, $filesToProcess);
            
            return response()->json([
                'message' => 'Started converting ' . $totalFiles . ' PDF files',
                'batch_id' => $batchId,
                'total_files' => $totalFiles
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
        
        foreach ($filesToProcess as $index => $pdfFile) {
            $filename = basename($pdfFile);
            
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
                    \Log::error("Failed to convert $filename: " . $result['error']);
                } else {
                    $successful++;
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
                \Log::error("Failed to convert $filename: " . $e->getMessage());
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
        
        // Check if PDF exists
        if (!Storage::disk('gcs')->exists($pdfPath)) {
            return ['error' => 'PDF file not found'];
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
    
    // Store batch progress (using Redis as example)
    protected function storeBatchProgress($batchId, $data)
    {
        // Using file-based storage for simplicity - use Redis in production
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
    
    // Get batch progress
    public function getBatchProgress($batchId)
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