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
    // Get form
    $form = Form::find($submission->dm_form_id);

    if (!$form || !$form->raw_schema) {
        throw new Exception("Form not found for submission ID: {$submission->id}");
    }

    $dataObject = json_decode($form->raw_schema);

    // Safe choices extraction
    $choices = $dataObject->asset->content->choices ?? [];
    if (!is_array($choices)) {
        $choices = [];
    }

    $location = [];

    // Safe shortcuts
    $source = $submission->sourceInformation;
    $family = $submission->familyInformation;
    $ownership = $submission->houseLandOwnership;
    $extra = is_array($submission->extraAttributesJson)
        ? $submission->extraAttributesJson
        : [];

    foreach ($choices as $value) {

        try {

            if (!isset($value->name)) {
                continue;
            }

            // Province
            if ($source?->survey_province === $value->name && isset($value->label[1])) {
                $location['province'] = $value->label[1];
            }

            // District
            if ($source?->district_name === $value->name && isset($value->label[1])) {
                $location['district'] = $value->label[1];
            }

            // Nahya
            if ($source?->nahya_number === $value->name && isset($value->label[0])) {
                $location['nahya'] = $value->label[0];
            }

            // Static fields
            if ($source?->province_code) {
                $location['province_code'] = $source->province_code;
            }

            if ($source?->city_code) {
                $location['city_code'] = $source->city_code;
            }

            if ($source?->district_code) {
                $location['district_code'] = $source->district_code;
            }

            if ( $value->name === ($source?->kbl_guzar_number ?? null)) {
                    $location['guzar'] = $value->label[0];
                }

                // Guzar (from choices or extra)
                if (
                    
                    $value->name === ($extra['guzar_number'] ?? null)
                ) {
                    if (isset($value->label[0])) {
                        $location['guzar'] = $value->label[0];
                    }
                }

            // Block
            if ($source?->block_number === $value->name && isset($value->label[0])) {
                $location['block'] = $value->label[0];
            }

            // House
            if ($source?->house_number === $value->name && isset($value->label[0])) {
                $location['house'] = $value->label[0];
            }

            // Manteqa
            if ($value->name === ($extra['manteqa'] ?? null) && isset($value->label[0])) {
                $location['manteqa'] = substr($value->label[0], 1);
            }

            // Origin province
            if ($family?->province_origin === $value->name && isset($value->label[1])) {
                $location['province_origin'] = $value->label[1];
            }

            // Origin district
            if ($family?->district_origin === $value->name && isset($value->label[1])) {
                $location['district_origin'] = $value->label[1];
            }

            // Status
            if ($submission->status === $value->name && isset($value->label[1])) {
                $location['status'] = $value->label[1];
            }

            // Year
            if ($submission->status === 'idp') {
                if ($submission->idp?->year_idp === $value->name && isset($value->label[1])) {
                    $location['year'] = $value->label[1];
                }
            }

            if ($submission->status === 'returnee') {
                if ($submission->returnee?->year_returnee === $value->name && isset($value->label[1])) {
                    $location['year'] = $value->label[1];
                }
            }

            // Ownership
            if ($ownership?->house_owner === $value->name && isset($value->label[1])) {
                $location['house_owner'] = $value->label[1];
            }

            if ($ownership?->type_tenure_document === $value->name && isset($value->label[1])) {
                $location['ownership_type'] = $value->label[1];
            }

            if ($ownership?->duration_lived_thishouse === $value->name && isset($value->label[1])) {
                $location['duration_lived_thishouse'] = $value->label[1];
            }

        } catch (\Throwable $e) {
            \Log::error('generatePDF loop error', [
                'submission_id' => $submission->id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
        }
    }

    // ✅ Fix guzar (your requirement)
    if (isset($location['guzar'])) {
        $guzar = $location['guzar'];

        if (strlen($guzar) === 3 && substr($guzar, 0, 1) === '0') {
            $location['guzar'] = substr($guzar, 1);
        }
    }

    // Projects
    if ($submission->projects && $submission->projects->isNotEmpty()) {
        $firstProject = $submission->projects->first();

        if ($firstProject && $firstProject->google_storage_folder) {
            $location['folder'] = $firstProject->google_storage_folder;
            $location['map_image'] = $this->getPath($location);
        }
    }

    // Template selection (safe)
    $bladeFile = 'pdf.template';

    if (($location['province_code'] ?? null) == 19) {
        $bladeFile = 'pdf.kunduz_template';
        \Log::info("Using kunduz template", ['submission_id' => $submission->id]);
    }

    $html = View::make($bladeFile, [
        'submission' => $submission,
        'location' => $location,
        'choices' => $choices,
    ])->render();

    // PDF generation
    $mpdf = new \Mpdf\Mpdf([
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

    return $mpdf->Output('', 'S'); // return as string
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
        $guzar = $location['guzar'] ?? null;

        if ($guzar !== null && strlen($guzar) === 3 && str_starts_with($guzar, '0')) {
            $guzar = substr($guzar, 1);
        }
        $parts = [
            $location['province_code'],
            $location['city_code'] ?? null,
            $location['district_code'] ?? null,
            $guzar,
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