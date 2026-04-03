<?php

namespace Modules\DataManagement\Jobs;

use Modules\DataManagement\Models\Submission;
use Modules\DataManagement\Models\Form;
use Modules\DataManagement\Models\BulkDownloadItem;
use Modules\DataManagement\Models\BulkDownloadLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use Exception;
use Carbon\Carbon;

class ProcessBulkDownloadItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;
    protected $submissionId;
    protected $itemId;
    
    public $timeout = 300;
    public $tries = 3;
    public $backoff = [5, 10, 30];

    public function __construct($batchId, $submissionId, $itemId)
    {
        $this->batchId = $batchId;
        $this->submissionId = $submissionId;
        $this->itemId = $itemId;
    }

    public function handle()
    {
        // Set memory limit and execution time (same as downloadProfile)
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);
        
        try {
            // Update item status to processing
            $item = BulkDownloadItem::find($this->itemId);
            if (!$item) {
                throw new Exception("Bulk download item not found with ID: {$this->itemId}");
            }

            $item->update([
                'status' => 'processing',
                'started_at' => now(),
                'progress' => 10
            ]);
            
            $this->log('info', 'Started processing submission', ['submission_id' => $this->submissionId]);

            // Load submission with all relationships (exactly like downloadProfile)
            $submission = Submission::with([
                'sourceInformation', 
                'familyInformation', 
                'headFamily', 
                'idp', 
                'returnee', 
                'interviewwee', 
                'photoSection', 
                'houseLandOwnership',
                'projects'
            ])->find($this->submissionId);

            if (!$submission) {
                throw new Exception("Submission not found with ID: {$this->submissionId}");
            }

            $item->update(['progress' => 30]);

            // Generate PDF using the exact same logic as downloadProfile
            $pdfContent = $this->generatePDF($submission);
            
            $item->update(['progress' => 70]);

            // Generate filename using the exact same logic as downloadProfile
            $filename = $this->generateFilename($submission);
            
            // Store PDF
            $path = "bulk-downloads/batch-{$this->batchId}/{$filename}";
            Storage::disk('public')->put($path, $pdfContent);
            
            $item->update(['progress' => 90]);

            // Update item as completed
            $item->update([
                'status' => 'completed',
                'progress' => 100,
                'file_name' => $filename,
                'file_path' => $path,
                'completed_at' => now()
            ]);

            // Update batch counters
            if ($item->batch) {
                $item->batch->updateCounters();
            }

            $this->log('info', 'Successfully processed submission', [
                'submission_id' => $this->submissionId,
                'file' => $filename
            ]);

        } catch (Exception $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    private function handleFailure(Exception $e)
    {
        $item = BulkDownloadItem::find($this->itemId);
        if ($item) {
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);
            
            // Update batch counters
            if ($item->batch) {
                $item->batch->updateCounters();
            }
        }

        $this->log('error', 'Failed to process submission', [
            'submission_id' => $this->submissionId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Generate PDF for submission - EXACTLY like downloadProfile
     */
    private function generatePDF($submission)
    {
        // Get form data
        $form = Form::find($submission->dm_form_id);
        if (!$form || !$form->raw_schema) {
            throw new Exception("Form not found for submission ID: {$submission->id}");
        }

        $dataObject = json_decode($form->raw_schema);
        if (!$dataObject || !isset($dataObject->asset->content->choices)) {
            throw new Exception("Invalid form schema for submission ID: {$submission->id}");
        }

        $choices = $dataObject->asset->content->choices;
        
        // Get location data using the EXACT same logic as downloadProfile
        $location = [];
        $firstLetter = '';

        foreach ($choices as $key => $value) {
            if (isset($value->name) && $value->name === $submission->sourceInformation->survey_province) {
                if (isset($value->label[1])) {
                    $location['province'] = $value->label[1];
                }
            }
            if (isset($value->name) && $value->name === $submission->sourceInformation->district_name) {
                if (isset($value->label[1])) {
                    $location['district'] = $value->label[1];
         
                }
            }
            if (isset($value->name) && $value->name === $submission->sourceInformation->nahya_number) {
                if (isset($value->label[0])) {
                    $location['nahya'] = $value->label[0];
                }
            }
            
            if ($submission->sourceInformation->province_code) {
                $location['province_code'] = $submission->sourceInformation->province_code;
            }
            if ($submission->sourceInformation->city_code) {
                $location['city_code'] = $submission->sourceInformation->city_code;
            }
            if ($submission->sourceInformation->district_code) {
                $location['district_code'] = $submission->sourceInformation->district_code;
            }

            if (isset($value->name) && 
                ($value->name === ($submission->sourceInformation->kbl_guzar_number ?? null) || 
                $value->name === ($submission->extraAttributesJson['guzar_number'] ?? null))) {
                
                if (isset($value->label[0])) {
                    $location['guzar'] = substr($value->label[0], 1);
                }
            }
            
            if (isset($value->name) && $value->name === $submission->sourceInformation->block_number) {
                if (isset($value->label[0])) {
                    $location['block'] = $value->label[0];
                }
            }
            if (isset($value->name) && $value->name === $submission->sourceInformation->house_number) {
                if (isset($value->label[0])) {
                    $location['house'] = $value->label[0];
                }
            }
            if (isset($value->name) && $value->name === ($submission->extraAttributesJson['manteqa'] ?? 'falst')) {
                if (isset($value->label[0])) {
                    $location['manteqa'] = substr($value->label[0], 1);
                }
            }
            if (isset($value->name) && $value->name === $submission->familyInformation->province_origin) {
                if (isset($value->label[1])) {
                    $location['province_origin'] = $value->label[1];
                }
            }
            if (isset($value->name) && $value->name === $submission->familyInformation->district_origin) {
                if (isset($value->label[1])) {
                    $location['district_origin'] = $value->label[1];
                }
            }
            if (isset($value->name) && $value->name === $submission->status) {
                if (isset($value->label[1])) {
                    $location['status'] = $value->label[1];
                }
            }
            if ($submission->status === 'idp') {
                if (isset($value->name) && $value->name === $submission->idp->year_idp) {
                    if (isset($value->label[1])) {
                        $location['year'] = $value->label[1];
                    }
                }
            } elseif ($submission->status === 'returnee') {
                if (isset($value->name) && $value->name === $submission->returnee->year_returnee) {
                    if (isset($value->label[1])) {
                        $location['year'] = $value->label[1];
                    }
                }
            } 
            
            if (isset($value->name) && $value->name === $submission->houseLandOwnership->house_owner) {
                if (isset($value->label[1])) {
                    $location['house_owner'] = $value->label[1];
                }
            }
            if (isset($value->name) && $value->name === $submission->houseLandOwnership->type_tenure_document) {
                if (isset($value->label[1])) {
                    $location['ownership_type'] = $value->label[1];
                }
            }
            if (isset($value->name) && $value->name === $submission->houseLandOwnership->duration_lived_thishouse) {
                if (isset($value->label[1])) {
                    $location['duration_lived_thishouse'] = $value->label[1];
                }
            }
        }

        // Check if projects relationship exists and has data
        if ($submission->projects && $submission->projects->isNotEmpty()) {
            $firstProject = $submission->projects->first();
            if ($firstProject && $firstProject->google_storage_folder) {
                $location['folder'] = $firstProject->google_storage_folder;
                $map_path = $this->getPath($location);
                $location['map_image'] = $map_path;
            }
        }

        $bladeFile = 'pdf.template';
        if ($location['province_code'] == 19) {
            $bladeFile = 'pdf.kunduz_template';
        }


        $html = View::make($bladeFile, [
            'submission' => $submission,
            'location' => $location,
            'choices' => $choices,
        ])->render();
        

        // Create PDF with EXACT same configuration as downloadProfile
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf-temp'),
            'format' => 'A4',
            'mode' => 'utf-8',
            'default_font' => 'dejavusans',
            'default_font_size' => 9,
            'directionality' => 'rtl',
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_header' => 2,
            'margin_footer' => 2,
        ]);

        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S'); // 'S' for string output
    }

    /**
     * Generate filename for PDF - EXACTLY like downloadProfile
     */
    private function generateFilename($submission)
    {
        if (!$submission->sourceInformation) {
            return "submission-{$submission->id}.pdf";
        }

        $si = $submission->sourceInformation;
        
        // Get location data using the same logic
        $form = Form::find($submission->dm_form_id);
        $location = [];
        
        if ($form && $form->raw_schema) {
            $dataObject = json_decode($form->raw_schema);
            if ($dataObject && isset($dataObject->asset->content->choices)) {
                $choices = $dataObject->asset->content->choices;
                
                foreach ($choices as $value) {
                    if ($si->kbl_guzar_number && isset($value->name) && $value->name === $si->kbl_guzar_number) {
                        if (isset($value->label[0])) {
                            $location['guzar'] = substr($value->label[0], 1);
                        }
                    }
                    if ($si->block_number && isset($value->name) && $value->name === $si->block_number) {
                        if (isset($value->label[0])) {
                            $location['block'] = $value->label[0];
                        }
                    }
                    if ($si->house_number && isset($value->name) && $value->name === $si->house_number) {
                        if (isset($value->label[0])) {
                            $location['house'] = $value->label[0];
                        }
                    }
                }
            }
        }
        
        // Fallback to direct values if not found in choices
        if (!isset($location['guzar'])) {
            $location['guzar'] = $si->kbl_guzar_number ?? '';
        }
        if (!isset($location['block'])) {
            $location['block'] = $si->block_number ?? '';
        }
        if (!isset($location['house'])) {
            $location['house'] = $si->house_number ?? '';
        }
        
        $name = $si->province_code . '-' . $si->city_code . '-' . $si->district_code . '-' . 
                ($location['guzar'] ?? '') . '-' . ($location['block'] ?? '') . '-' . ($location['house'] ?? '');
        
        // Sanitize filename
        $name = preg_replace('/[^A-Za-z0-9\-]/', '', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');
        
        return $name . '.pdf';
    }

    /**
     * Get map path (matches your existing getPath method)
     */
    private function getPath($location)
    {
        if (empty($location) || empty($location['folder'])) {
            return asset('images/default.png');
        }
        $expiration = Carbon::now()->addMinutes(10);
        $parts = [
            $location['province_code'],
            $location['city_code'] ?? null,
            $location['district_code'] ?? null,
            $location['guzar'] ?? null,
            $location['block'] ?? null,
            $location['house'] ?? null
        ];

        // Filter out empty values
        $parts = array_filter($parts, function($value) {
            return !empty($value);
        });
        $code = implode('-', $parts);

        $filePath = "{$location['folder']}/"
            . $code . '.jpg';

        if (Storage::disk('gcs')->exists($filePath)) {
            return Storage::disk('gcs')->temporaryUrl($filePath, $expiration);
        }

        return asset('images/default.png');
    }

    /**
     * Create log entry
     */
    private function log($level, $message, $context = [])
    {
        try {
            BulkDownloadLog::create([
                'batch_id' => $this->batchId,
                'submission_id' => $this->submissionId,
                'level' => $level,
                'message' => $message,
                'context' => $context
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to create bulk download log', [
                'error' => $e->getMessage(),
                'batch_id' => $this->batchId,
                'submission_id' => $this->submissionId
            ]);
        }
    }

    /**
     * Handle job failure (called when job fails after all retries)
     */
    public function failed(Exception $e)
    {
        $this->log('error', 'Job failed after all retries', [
            'submission_id' => $this->submissionId,
            'error' => $e->getMessage()
        ]);
    }
}