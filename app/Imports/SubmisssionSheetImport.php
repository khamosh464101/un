<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Modules\DataManagement\Models\{
    Submission, SourceInformation, FamilyInformation, HeadFamily, Interviewwee,
    Composition, Idp, Returnee, TypeReturnDocumentPhoto, ExtremelyVulnerableMember,
    AccessCivilDocumentMale, AccessCivilDocumentFemale, HouseLandOwnership,
    LandOwnershipDocument, HouseCondition, HouseProblemAreaPhoto, AccessBasicService,
    FoodConsumptionScore, HouseholdStrategyFood, CommunityAvailability, Livelihood,
    DurableSolution, SkillIdea, Resettlement, RecentAssistance, InfrasttructureService,
    PhotoSection, Form, SubmissionStatus
};
use Modules\DataManagement\Services\{KoboService, KoboSubmissionParser};
use Illuminate\Support\Str;
use DB;

HeadingRowFormatter::default('none');

class SubmissionSheetImport implements ToModel, WithStartRow, WithHeadingRow, WithChunkReading, WithLimit
{
    protected $startRow;
    protected $limit;
    protected $chunkSize = 50; // Reduced chunk size
    protected $currentRow = 0;
    protected static $formData; // Shared across all instances

    public function __construct($startRow, $limit)
    {
        $this->startRow = $startRow;
        $this->limit = $limit;
    }

    public function setChunkSize(int $chunkSize): self
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }

    public function chunkSize(): int { return $this->chunkSize; }
    public function startRow(): int { return $this->startRow ?? 1; }
    public function limit(): int { return $this->limit ?? 10; }

    public function model(array $row)
    {
        DB::transaction(function () use ($row) {
            // Skip if exists
            if (Submission::where('_id', $row['_id'])->exists()) {
                return null;
            }

            // Load form data once (shared across all rows)
            if (!self::$formData) {
                self::$formData = [
                    'form' => Form::first(['id', 'raw_schema']),
                    'defaultStatus' => SubmissionStatus::where('is_default', true)->first(['id'])
                ];
                self::$formData['schema'] = json_decode(self::$formData['form']->raw_schema);
            }

            // Process data
            $processed = $this->processRowData($row, self::$formData['schema']);
            
            // Create submission
            $submission = Submission::create(array_merge($processed['submission'], [
                '_id' => $row['_id'],
                'dm_form_id' => self::$formData['form']->id,
                'submission_status_id' => self::$formData['defaultStatus']->id ?? 1
            ]));

            // Create related models with bulk operations where possible
            $this->createRelatedModels($processed, $submission);

            // Process attachments
            $this->processAttachments($submission);

            // Garbage collection every 10 rows
            if (++$this->currentRow % 10 === 0) {
                gc_collect_cycles();
            }
        });
    }

    protected function processRowData(array $row, object $schema): array
    {
        $row['1.7 Block Code Number'] = str_pad($row['1.7 Block Code Number'], 3, "0", STR_PAD_LEFT);
        $row['1.6 Guzar Code Number'] = str_pad($row['1.6 Guzar Code Number'], 3, "0", STR_PAD_LEFT);

        $survey = $schema->asset->content->survey ?? [];
        $choices = $schema->asset->content->choices ?? [];
        
        $data = [];
        foreach ($survey as $surveyItem) {
            if (!isset($surveyItem->name)) continue;
            
            $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
            if ($result === 12345) continue;
            
            // Categorize by model type
            $this->categorizeData($surveyItem->name, $result, $data);
        }
        
        return $data;
    }

    protected function categorizeData(string $fieldName, $value, array &$data): void
    {
        $modelMappings = [
            'submission' => (new Submission)->getIgnoreIdFillable(),
            'source_information' => (new SourceInformation)->getIgnoreIdFillable(),
            // ... all other model mappings
        ];
        
        foreach ($modelMappings as $model => $fields) {
            if (in_array($fieldName, $fields, true)) {
                $data[$model][$fieldName] = $value;
                break;
            }
        }
    }

    protected function createRelatedModels(array $data, Submission $submission): void
    {
        $models = [
            'source_information' => SourceInformation::class,
            'family_information' => FamilyInformation::class,
            // ... all other models
        ];
        
        foreach ($models as $key => $modelClass) {
            if (!empty($data[$key])) {
                $modelClass::create(array_merge(
                    $data[$key],
                    ['submission_id' => $submission->id]
                ));
            }
        }
        
        // Handle special cases
        $this->handleSpecialCases($data, $submission);
    }

    protected function handleSpecialCases(array $data, Submission $submission): void
    {
        // Handle conditional relationships
        if ($submission->status === "idp" && !empty($data['idp'])) {
            Idp::create(array_merge($data['idp'], ['submission_id' => $submission->id]));
        }
        
        // Handle returnee case with photos
        if ($submission->status === "returnee" && !empty($data['returnee'])) {
            $returnee = Returnee::create(array_merge(
                $data['returnee'],
                ['submission_id' => $submission->id]
            ));
            
            // Process photos in bulk if they exist
            $this->processReturneePhotos($returnee);
        }
    }

    protected function processAttachments(Submission $submission): void
    {
        $service = new KoboService();
        $kobo_submission = $service->getSubmission($submission->_id);
        
        if ($kobo_submission) {
            $kobo_submission = $this->cleanKoboSubmissionKeys($kobo_submission);
            
            // Process image attachments in bulk
            $attachments = array_filter($kobo_submission['_attachments'] ?? [], function($attachment) {
                return Str::startsWith($attachment['mimetype'], 'image/');
            });
            
            foreach ($attachments as $attachment) {
                $service->downloadAttachment($attachment);
            }
        }
    }

    // ... keep your existing helper methods (getSingleValue, cleanKoboSubmissionKeys, etc.)
}