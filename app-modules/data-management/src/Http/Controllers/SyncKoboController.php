<?php

namespace Modules\DataManagement\Http\Controllers;
use Modules\DataManagement\Http\Controllers\FormatController;
use Modules\DataManagement\Services\KoboService;
use Modules\DataManagement\Services\KoboSubmissionParser;
use Illuminate\Http\Request;
use Modules\DataManagement\Models\Form;
use Modules\DataManagement\Models\Submission;

use App\Imports\CustomColumnsImport;
use Maatwebsite\Excel\Facades\Excel;
use Storage;
use Modules\Projects\Models\Project;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;



class SyncKoboController
{
    protected $kobo;
    protected $parser;

    public function __construct(KoboService $kobo, KoboSubmissionParser $submissionParser)
    {
        $this->kobo = $kobo;
        $this->parser = $submissionParser;
    }


    public function listForms(Request $request)
    {
        $startRow = intval($request->startRow);
        $limit = intval($request->limitRow);
        $forms = $this->kobo->getFormSubmissions($startRow, $limit, $request->formId);
        $data = $forms; // Ensure this is a JSON-decoded array
        $projectId = $request->projectId;
        // do {
            foreach ($data['results'] as $key => $value) {
                $submission = Submission::where('_id', $value['_id'])->first();
                if ($submission) {
                    logger()->info('343434343434343434');
                    continue;
                }
                $result = $this->cleanKoboSubmissionKeys($value);
                logger()->info($result);
                $this->parser->parseAndReturn($result, $projectId);
            }

            return response()->json(['message' => 'Successfully inserted into the system from kobo.'], 201);

    }


    public function insertFormExcel(Request $request)
    {
        logger()->info('Memory usage: ' . (memory_get_usage(true) / 1024 / 1024) . ' MB');

        // Validate project
        $project = Project::find($request->projectId);
        if (!$project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $disk = Storage::disk('excel');

        if (!$disk->exists($request->filename)) {
            return response()->json(['message' => 'Excel file not found.'], 404);
        }

        $path = $disk->path($request->filename);

        // Dynamic parameters
        $startRow = (int) ($request->startRow ?? 2);
        $limitRow = (int) ($request->limitRow ?? 100);

        // Collect columns from project mapping
        $columns = $project->importFormatMaps
            ->pluck('excel_file_column_name')
            ->toArray();

        try {
            $formatController = new FormatController();
            $result = $formatController->getExcelIndexColumnAndHeaderMap($path);

            $idColumn = collect($result)->firstWhere('label', '_id');
            if (!$idColumn) {
                return response()->json([
                    'message' => '_id column not found in Excel file'
                ], 400);
            }

            $columns[] = $idColumn['value'];

        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getCode() ?: 500);
        }

        // Import Excel rows
        $import = new CustomColumnsImport($startRow, $limitRow, $columns);
        $data   = $import->toArray($path);
        $sheetData = $data[0] ?? [];

        if (empty($sheetData)) {
            return response()->json(['message' => 'No rows found in Excel.'], 400);
        }

        // Load form schema
        $form = Form::where('form_id', $project->kobo_copy_project_id ?? $project->kobo_project_id)->first();

        if (!$form) {
            return response()->json(['message' => 'Form not found'], 404);
        }

        $schema  = json_decode($form->raw_schema);
        $choices = $schema->asset->content->choices ?? [];
        $survey  = $schema->asset->content->survey  ?? [];

        // ── Collect all _id values from the Excel rows ───────────────────────
        $idColumnKey = $idColumn['value'];

        $submissionIds = collect($sheetData)
            ->pluck($idColumnKey)
            ->filter()           // remove nulls/empty
            ->unique()
            ->values()
            ->toArray();

        if (empty($submissionIds)) {
            return response()->json(['message' => 'No valid _id values found in selected rows.'], 400);
        }

        // ── Filter out already-existing submissions ───────────────────────────
        $existingIds = Submission::whereIn('_id', $submissionIds)
            ->pluck('_id')
            ->toArray();

        $newIds = array_values(array_diff($submissionIds, $existingIds));

        if (empty($newIds)) {
            return response()->json([
                'message'    => 'All selected submissions already exist.',
                'total_rows' => count($sheetData),
                'skipped'    => count($existingIds),
                'inserted'   => 0,
            ]);
        }

        // ── Single bulk API call to Kobo for all new _ids ─────────────────────
        $koboSubmissionsMap = $this->kobo->getSubmissionsByIds($newIds, $project->kobo_project_id);

        if (empty($koboSubmissionsMap)) {
            logger()->warning('Kobo returned no submissions for ids: ' . implode(',', $newIds));
        }

        // ── Loop rows and process ─────────────────────────────────────────────
        $inserted = 0;
        $skipped  = count($existingIds);

        foreach ($sheetData as $row) {
            $submissionId = $row[$idColumnKey] ?? null;

            if (!$submissionId) {
                continue;
            }

            // Already exists — skip
            if (in_array($submissionId, $existingIds)) {
                logger()->info("Submission already exists: {$submissionId}");
                continue;
            }

            // Not returned by Kobo — skip
            if (!isset($koboSubmissionsMap[$submissionId])) {
                logger()->warning("Submission not found in Kobo response: {$submissionId}");
                continue;
            }

            $cleanedSubmission = $this->cleanKoboSubmissionKeys($koboSubmissionsMap[$submissionId]);

            // Apply field mappings from Excel columns
            foreach ($project->importFormatMaps as $map) {
                $surveyItem = collect($survey)->firstWhere('name', $map->kobo_form_field_name);
                $labelValue = $row[$map->excel_file_column_name] ?? null;
                $mappedValue = $this->checkChoice($choices, $labelValue, $surveyItem);

                if ($mappedValue !== false) {
                    $cleanedSubmission[$map->kobo_form_field_name] = $mappedValue;
                } else {
                    if (isset($surveyItem->type) && $surveyItem->type === 'date' && $labelValue) {
                        $cleanedSubmission[$map->kobo_form_field_name] = $this->getDate($labelValue);
                    } else {
                        $cleanedSubmission[$map->kobo_form_field_name] = $labelValue;
                    }
                }
            }

            // Parse and insert submission
            $this->parser->parseAndReturn($cleanedSubmission, $project->id);
            $inserted++;
        }

        logger()->info('Memory usage after import: ' . (memory_get_usage(true) / 1024 / 1024) . ' MB');

        return response()->json([
            'data'             => $sheetData,
            'columns_requested'=> $columns,
            'total_rows'       => count($sheetData),
            'inserted'         => $inserted,
            'skipped'          => $skipped,
            'memory_used'      => (memory_get_usage(true) / 1024 / 1024) . ' MB',
        ]);
    }

    public function cleanKoboSubmissionKeys(array $submission): array
    {
        $cleaned = [];

        foreach ($submission as $key => $value) {
            // Get the last part after the last slash
            $parts = explode('/', $key);
            $attributeName = end($parts);
            $cleaned[$attributeName] = $value;
        }

        return $cleaned;
    }

    public function getSubmission()
    {
        $submission = $this->kobo->getSubmission();
        return $submission;
    }


    private function mergeExcelAndKobo(array $koboData, array $excelRow, array $mappedColumns): array
    {
        foreach ($mappedColumns as $column) {
            if (isset($excelRow[$column])) {
                $koboData[$column] = $excelRow[$column];
            }
        }

        return $koboData;
    }

    private function checkChoice($choices, $labelValue, $surveyItem) {
        if (isset($surveyItem->select_from_list_name)) {
     
            foreach ($choices as $choice) {
                if ($choice->label[0] == $labelValue && $surveyItem->select_from_list_name == $choice->list_name) {
                    return $choice->name;
                }
            }
        }
        
        return false;
    }

    private function getDate($date): Carbon
    {
        return Carbon::instance(Date::excelToDateTimeObject($date));
    }
    
}
