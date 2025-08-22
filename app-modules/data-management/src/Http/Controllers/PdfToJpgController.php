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

    // Convert PDF to JPG
    public function convertPdfToJpg(Request $request, $filename)
    {
        try {
            // Remove .pdf extension if present
            $baseName = str_replace('.pdf', '', $filename);
            $pdfPath = 'pdf/' . $baseName . '.pdf';
            
            // Check if PDF exists
            if (!Storage::disk('gcs')->exists($pdfPath)) {
                return response()->json(['error' => 'PDF file not found'], 404);
            }
            
            // Download PDF to temporary location
            $tempPdfPath = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';
            Storage::disk('gcs')->get($pdfPath, $tempPdfPath);
            
            // Convert PDF to JPG
            $pdf = new Pdf($tempPdfPath);
            $tempJpgPath = tempnam(sys_get_temp_dir(), 'jpg') . '.jpg';
            $pdf->saveImage($tempJpgPath);
            
            // Upload JPG to storage
            $jpgPath = 'jpg/' . $baseName . '.jpg';
            Storage::disk('gcs')->put($jpgPath, file_get_contents($tempJpgPath));
            
            // Delete the original PDF
            Storage::disk('gcs')->delete($pdfPath);
            
            // Clean up temporary files
            unlink($tempPdfPath);
            unlink($tempJpgPath);
            
            return response()->json([
                'message' => 'PDF converted to JPG successfully',
                'jpg_path' => $jpgPath
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}





