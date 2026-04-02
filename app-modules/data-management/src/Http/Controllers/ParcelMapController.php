<?php

namespace Modules\DataManagement\Http\Controllers;
use Modules\DataManagement\Services\ParcelImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\DataManagement\Models\ParcelImage;

class ParcelMapController
{
    protected $imageService;
    
    public function __construct(ParcelImageService $imageService)
    {
        $this->imageService = $imageService;
    }
    
    /**
     * Initialize generation for all parcels
     */
    public function initialize()
    {
        try {
            $initialized = $this->imageService->initializeForAllParcels();
            
            return response()->json([
                'success' => true,
                'message' => "Initialized {$initialized} parcels for image generation",
                'initialized' => $initialized
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to initialize generation: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to initialize generation'
            ], 500);
        }
    }
    
    /**
     * Process a batch of parcels
     */
    public function processBatch(Request $request)
    {
        $request->validate([
            'batch_size' => 'integer|min:1|max:50'
        ]);
        
        try {
            $batchSize = $request->input('batch_size', 10);
            $results = $this->imageService->processBatch($batchSize);
            
            return response()->json([
                'success' => true,
                'message' => "Processed {$results['processed']} parcels",
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to process batch: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to process batch'
            ], 500);
        }
    }
    
    /**
     * Get generation progress
     */
    public function getProgress()
    {
        try {
            $progress = $this->imageService->getProgress();
            
            return response()->json([
                'success' => true,
                'progress' => $progress
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get progress: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get progress'
            ], 500);
        }
    }
    
    /**
     * Retry failed generations
     */
    public function retryFailed(Request $request)
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100'
        ]);
        
        try {
            $limit = $request->input('limit', 50);
            
            // Reset failed to pending
            $count = ParcelImage::where('status', 'failed')
                ->where('retry_count', '<', 3)
                ->limit($limit)
                ->update(['status' => 'pending']);
            
            return response()->json([
                'success' => true,
                'message' => "Reset {$count} failed items to pending",
                'reset' => $count
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retry: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retry'
            ], 500);
        }
    }
}