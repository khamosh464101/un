<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
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
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

HeadingRowFormatter::default('none');

class SubmissionSheetImport implements ToModel, WithStartRow, WithHeadingRow, ShouldQueue, WithChunkReading, WithBatchInserts, WithLimit
{
    protected $startRow;
    protected $limit;
    protected $chunkSize = 50;
    protected $currentRow = 0;
    protected static $formData;

    private const DATES = [
        'today', 'start', 'end', 
        'date_return_home_country', 
        'type_return_document_date', 
        'house_document_date'
    ];

    private const PHOTOS = [
        'hoh_nic_photo', 'inter_nic_photo', 'inter_nic_photo_owner',
        'water_point_photo', 'access_sanitation_photo',
        'access_education_photo', 'access_health_photo', 'access_road_photo'
    ];

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

    public function batchSize(): int
{
    return $this->chunkSize;
}

    public function chunkSize(): int { return $this->chunkSize; }
    public function startRow(): int { return $this->startRow ?? 1; }
    public function limit(): int { return $this->limit ?? 10; }

    public function model(array $row)
    {
        logger()->info('job is working at the end');
        DB::transaction(function () use ($row) {
            if (Submission::where('_id', $row['_id'])->exists()) {
                return null;
            }

            if (!self::$formData) {
                self::$formData = [
                    'form' => Form::first(['id', 'raw_schema']),
                    'defaultStatus' => SubmissionStatus::where('is_default', true)->first(['id'])
                ];
                self::$formData['schema'] = json_decode(self::$formData['form']->raw_schema);
            }

            $processed = $this->processRowData($row, self::$formData['schema']);
            
            $submission = Submission::create(array_merge($processed['submission'], [
                '_id' => $row['_id'],
                'dm_form_id' => self::$formData['form']->id,
                'submission_status_id' => self::$formData['defaultStatus']->id ?? 1
            ]));

            $this->createCoreModels($processed, $submission);
            $this->handleSpecialModels($processed, $submission);
            $this->processAttachments($submission);

            if (++$this->currentRow % 10 === 0) {
                gc_collect_cycles();
            }
        });
    }

    protected function processRowData(array $row, object $schema): array
    {
        $row['1.7 Block Code Number'] = str_pad($row['1.7 Block Code Number'], 3, "0", STR_PAD_LEFT);
        $row['1.6 Guzar Code Number'] = str_pad($row['1.6 Guzar Code Number'], 3, "0", STR_PAD_LEFT);

        $data = [];
        $survey = $schema->asset->content->survey ?? [];
        $choices = $schema->asset->content->choices ?? [];

        foreach ($survey as $surveyItem) {
            if (!isset($surveyItem->name)) continue;
            
            $result = $this->getSingleValue($surveyItem, $row, $choices, $surveyItem->name);
            if ($result === 12345) continue;
            
            $this->categorizeData($surveyItem->name, $result, $data);
        }
        
        return $data;
    }

    protected function categorizeData(string $fieldName, $value, array &$data): void
    {
        $modelMappings = [
            'submission' => (new Submission)->getIgnoreIdFillable(),
            'source_information' => (new SourceInformation)->getIgnoreIdFillable(),
            'family_information' => (new FamilyInformation)->getIgnoreIdFillable(),
            'head_family' => (new HeadFamily)->getIgnoreIdFillable(),
            'interviewwee' => (new Interviewwee)->getIgnoreIdFillable(),
            'composition' => (new Composition)->getIgnoreIdFillable(),
            'idp' => (new Idp)->getIgnoreIdFillable(),
            'returnee' => (new Returnee)->getIgnoreIdFillable(),
            'extremely_vulnerable_member' => (new ExtremelyVulnerableMember)->getIgnoreIdFillable(),
            'access_civil_document_male' => (new AccessCivilDocumentMale)->getIgnoreIdFillable(),
            'access_civil_document_female' => (new AccessCivilDocumentFemale)->getIgnoreIdFillable(),
            'house_land_ownership' => (new HouseLandOwnership)->getIgnoreIdFillable(),
            'house_condition' => (new HouseCondition)->getIgnoreIdFillable(),
            'access_basic_service' => (new AccessBasicService)->getIgnoreIdFillable(),
            'food_consumption_score' => (new FoodConsumptionScore)->getIgnoreIdFillable(),
            'household_strategy_food' => (new HouseholdStrategyFood)->getIgnoreIdFillable(),
            'community_availability' => (new CommunityAvailability)->getIgnoreIdFillable(),
            'livelihood' => (new Livelihood)->getIgnoreIdFillable(),
            'durable_solution' => (new DurableSolution)->getIgnoreIdFillable(),
            'skill_idea' => (new SkillIdea)->getIgnoreIdFillable(),
            'resettlement' => (new Resettlement)->getIgnoreIdFillable(),
            'recent_assistance' => (new RecentAssistance)->getIgnoreIdFillable(),
            'Infrasttructure_service' => (new InfrasttructureService)->getIgnoreIdFillable(),
            'photo_section' => (new PhotoSection)->getIgnoreIdFillable()
        ];
        
        foreach ($modelMappings as $model => $fields) {
            if (in_array($fieldName, $fields, true)) {
                $data[$model][$fieldName] = $value;
                break;
            }
        }
    }

    protected function createCoreModels(array $data, Submission $submission): void
    {
        $coreModels = [
            'source_information' => SourceInformation::class,
            'family_information' => FamilyInformation::class,
            'composition' => Composition::class,
            'access_basic_service' => AccessBasicService::class,
            'food_consumption_score' => FoodConsumptionScore::class,
            'household_strategy_food' => HouseholdStrategyFood::class,
            'community_availability' => CommunityAvailability::class,
            'livelihood' => Livelihood::class,
            'durable_solution' => DurableSolution::class,
            'skill_idea' => SkillIdea::class,
            'resettlement' => Resettlement::class,
            'recent_assistance' => RecentAssistance::class
        ];
        
        foreach ($coreModels as $key => $modelClass) {
            if (!empty($data[$key])) {
                $modelClass::create(array_merge(
                    $data[$key],
                    ['submission_id' => $submission->id]
                ));
            }
        }
    }

    protected function handleSpecialModels(array $data, Submission $submission): void
    {
        // Handle family information cases
        if (!empty($data['family_information'])) {
            $familyInfo = FamilyInformation::create(array_merge(
                $data['family_information'],
                ['submission_id' => $submission->id]
            ));
            
            if ($familyInfo->hof_or_interviewee === "yes" && !empty($data['head_family'])) {
                HeadFamily::create(array_merge(
                    $data['head_family'],
                    ['submission_id' => $submission->id]
                ));
            } elseif (!empty($data['interviewwee'])) {
                Interviewwee::create(array_merge(
                    $data['interviewwee'],
                    ['submission_id' => $submission->id]
                ));
            }
        }
        
        // Handle IDP/Returnee cases
        if ($submission->status === "idp" && !empty($data['idp'])) {
            Idp::create(array_merge(
                $data['idp'],
                ['submission_id' => $submission->id]
            ));
        } elseif ($submission->status === "returnee" && !empty($data['returnee'])) {
            $returnee = Returnee::create(array_merge(
                $data['returnee'],
                ['submission_id' => $submission->id]
            ));
            $this->processReturneePhotos($returnee);
        }
        
        // Process other special models
        $specialModels = [
            'extremely_vulnerable_member' => ExtremelyVulnerableMember::class,
            'access_civil_document_male' => AccessCivilDocumentMale::class,
            'access_civil_document_female' => AccessCivilDocumentFemale::class,
            'house_land_ownership' => HouseLandOwnership::class,
            'house_condition' => HouseCondition::class
        ];
        
        foreach ($specialModels as $key => $modelClass) {
            if (!empty($data[$key])) {
                $model = $modelClass::create(array_merge(
                    $data[$key],
                    ['submission_id' => $submission->id]
                ));
                
                // Handle special cases for each model
                $this->processModelSpecialCases($key, $model, $submission);
            }
        }
        
        // Process parser services
        $this->processParserServices($submission);
    }

    protected function processModelSpecialCases(string $modelType, $model, Submission $submission): void
    {
        switch ($modelType) {
            case 'house_land_ownership':
                $this->processLandOwnershipDocs($model);
                break;
                
            case 'house_condition':
                $this->processHouseProblemPhotos($model);
                break;
        }
    }

    protected function processReturneePhotos(Returnee $returnee): void
    {
        $service = new KoboService();
        $submission = $service->getSubmission($returnee->submission->_id);
        
        if (isset($submission['house_document_photos'])) {
            $photos = [];
            foreach ($submission['house_document_photos'] as $value) {
                $photos[] = [
                    'type_return_document_photo' => $value["start_survey/returnee/house_document_photos/type_return_document_photo"],
                    'dm_returnee_id' => $returnee->id
                ];
            }
            TypeReturnDocumentPhoto::insert($photos);
        }
    }

    protected function processLandOwnershipDocs(HouseLandOwnership $model): void
    {
        $service = new KoboService();
        $submission = $service->getSubmission($model->submission->_id);
        
        if (isset($submission['house_document_photo_repeat'])) {
            $docs = [];
            foreach ($submission['house_document_photo_repeat'] as $value) {
                $docs[] = [
                    'house_document_photo' => $value["start_survey/house_land_ownership/house_document_photo_repeat/house_document_photo"],
                    'dm_house_land_ownership_id' => $model->id
                ];
            }
            LandOwnershipDocument::insert($docs);
        }
    }

    protected function processHouseProblemPhotos(HouseCondition $model): void
    {
        $service = new KoboService();
        $submission = $service->getSubmission($model->submission->_id);
        
        if (isset($submission['house_problems_area_photos'])) {
            $photos = [];
            foreach ($submission['house_problems_area_photos'] as $value) {
                $photos[] = [
                    'current_house_problem_title' => $value["start_survey/house_condition/house_problems_area_photos/current_house_problem_title"],
                    'current_house_problem_photo' => $value["start_survey/house_condition/house_problems_area_photos/current_house_problem_photo"],
                    'dm_house_condition_id' => $model->id
                ];
            }
            HouseProblemAreaPhoto::insert($photos);
        }
    }

    protected function processAttachments(Submission $submission): void
    {
        $service = new KoboService();
        $kobo_submission = $service->getSubmission($submission->_id);
        
        if ($kobo_submission) {
            $kobo_submission = $this->cleanKoboSubmissionKeys($kobo_submission);
            
            $attachments = array_filter($kobo_submission['_attachments'] ?? [], function($attachment) {
                return Str::startsWith($attachment['mimetype'], 'image/');
            });
            
            foreach ($attachments as $attachment) {
                $folderName = $submission?->projects?->first()?->id;
                $service->downloadAttachment($attachment, "kobo-attachments/{$folderName}");
            }
        }
    }

    protected function processParserServices(Submission $submission): void
    {
        $service = new KoboService();
        $parser = new KoboSubmissionParser($service);
        $kobo_submission = $service->getSubmission($submission->_id);
        
        if ($kobo_submission) {
            $kobo_submission = $this->cleanKoboSubmissionKeys($kobo_submission);
            $parser->createInfrasttructureService($kobo_submission, $submission);
            $parser->createPhotoSection($kobo_submission, $submission);
        }
    }

    protected function getSingleValue($surveyItem, $row, $choices, $fieldName)
    {
        if (isset($row[$surveyItem->name])) {
            $value = $row[$surveyItem->name];
            if (in_array($fieldName, self::DATES)) {
                return $this->getDate($value);
            }
            return $value;
        }

        if (isset($surveyItem->label) && isset($row[$surveyItem->label[0]])) {
            $labelValue = $row[$surveyItem->label[0]];
            
            if (in_array($fieldName, self::PHOTOS)) {
                return $labelValue;
            }
            
            if (in_array($fieldName, self::DATES)) {
                return $this->getDate($labelValue);
            }
            
            if (isset($surveyItem->select_from_list_name)) {
                foreach ($choices as $choice) {
                    if ($choice->label[0] === $labelValue && 
                        $surveyItem->select_from_list_name === $choice->list_name) {
                        return $choice->name;
                    }
                }
            }
            
            return $labelValue;
        }
        
        return 12345;
    }

    protected function getDate($date): Carbon
    {
        return Carbon::instance(Date::excelToDateTimeObject($date));
    }

    public function cleanKoboSubmissionKeys(array $submission): array
    {
        $cleaned = [];
        foreach ($submission as $key => $value) {
            $parts = explode('/', $key);
            $attributeName = end($parts);
            $cleaned[$attributeName] = $value;
        }
        return $cleaned;
    }
}