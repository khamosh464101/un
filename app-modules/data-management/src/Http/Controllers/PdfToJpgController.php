<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToImage\Pdf;
use Exception;

class PdfToJpgController
{
      // Get folder summaries
    public function getFolderSummary()
    {
        try {
            $jpgFiles = Storage::disk('google')->files('jpg');
            $pdfFiles = Storage::disk('google')->files('pdf');
            
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

    // Convert PDF to JPG
    public function convertPdfToJpg(Request $request, $filename)
    {
        try {
            // Remove .pdf extension if present
            $baseName = str_replace('.pdf', '', $filename);
            $pdfPath = 'pdf/' . $baseName . '.pdf';
            
            // Check if PDF exists
            if (!Storage::disk('google')->exists($pdfPath)) {
                return response()->json(['error' => 'PDF file not found'], 404);
            }
            
            // Create temporary directory if it doesn't exist
            $tempDir = sys_get_temp_dir() . '/pdf_conversion/';
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Download PDF to temporary location with proper filename
            $tempPdfPath = $tempDir . $baseName . '.pdf';
            file_put_contents($tempPdfPath, Storage::disk('google')->get($pdfPath));
            
            // Verify the file was created
            if (!file_exists($tempPdfPath)) {
                return response()->json(['error' => 'Failed to create temporary PDF file'], 500);
            }
            
            // Check if Imagick is available
            if (extension_loaded('imagick')) {
                return $this->convertWithImagick($tempPdfPath, $pdfPath, $baseName, $tempDir);
            }
            
            // Check if Ghostscript is available
            if (!empty(shell_exec('which gs'))) {
                return $this->convertWithGhostscript($tempPdfPath, $pdfPath, $baseName, $tempDir);
            }
            
            return response()->json(['error' => 'No PDF conversion tool available. Please install Imagick or Ghostscript.'], 500);
            
        } catch (Exception $e) {
            // Clean up any temporary files
            if (isset($tempPdfPath) && file_exists($tempPdfPath)) {
                unlink($tempPdfPath);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
            $imagick->setResolution(150, 150); // Set resolution for better quality
            $imagick->readImage($tempPdfPath . '[0]'); // First page only
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90); // Set quality
            
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
            Storage::disk('google')->put($jpgPath, file_get_contents($tempJpgPath));
            
            // Delete the original PDF
            Storage::disk('google')->delete($pdfPath);
            
            return response()->json([
                'message' => 'PDF converted to JPG successfully using Imagick',
                'jpg_path' => $jpgPath,
                'method' => 'imagick'
            ]);
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
            Storage::disk('google')->put($jpgPath, file_get_contents($tempJpgPath));
            
            // Delete the original PDF
            Storage::disk('google')->delete($pdfPath);
            
            return response()->json([
                'message' => 'PDF converted to JPG successfully using Ghostscript',
                'jpg_path' => $jpgPath,
                'method' => 'ghostscript'
            ]);
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






