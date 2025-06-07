<?php

namespace Modules\DataManagement\Http\Controllers;

use App\Exports\SubmissionsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Modules\DataManagement\Models\Form;
use Modules\DataManagement\Models\Submission;
use Modules\DataManagement\Services\CreateSubmissionParser;
use Modules\DataManagement\Services\FilterableService;
use Modules\DataManagement\Services\ArchiveService;
use Modules\Projects\Models\Project;
use Mpdf\Mpdf;
use App\Imports\MultiTableImport;

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;

use Storage;

class SubmissionController
{
    protected $parser;
    protected $filterable;
    protected $archive;

    public function __construct(
        CreateSubmissionParser $submissionParser, 
        FilterableService $filterable,
        ArchiveService $archive
        )
    {
        $this->parser = $submissionParser;
        $this->filterable = $filterable->getFilterable();
        $this->archive = $archive;
    }

    public function index(Request $request) {
      

        $query = Submission::with([
            'sourceInformation', 
            'familyInformation', 
            'headFamily', 
            'interviewwee', 
            'composition',
            'idp',
            'returnee',
            'extremelyVulnerableMember',
            'accessCivilDocumentMale',
            'accessCivilDocumentFemale',
            'houseLandOwnership',
            'houseCondition',
            'accessBasicService',
            'foodConsumptionScore',
            'householdStrategyFood',
            'communityAvailability',
            'livelihood',
            'durableSolution',
            'skillIdea',
            'resettlement',
            'recentAssistance',
            'photoSection',
        ]);
        if ($request->project_id) {
            $query->whereHas('projects', function ($q) use ($request) {
                $q->where('projects.id', $request->project_id);
            });
        }
        foreach ($this->filterable as $field) {
            $query->when($request->filled($field), function ($q) use ($field, $request) {
                if (Str::contains($field, '__')) {
                    [$relation, $column] = explode('__', $field, 2);
                    $q->whereHas($relation, function ($subQ) use ($column, $request, $field) {
                        $subQ->where($column, $request->input($field));
                    });
                } else {
                    $q->where($field, $request->input($field));
                }
            });
        }

        return ["data" => $query->paginate(8), "filterable" => $this->filterable, "projects" => Project::select("id as value", "title as label")->get()];
    }
    public function getForm() {
        
        return response()->json(Form::first());
    }

    public function store (Request $request) {
        // if ($request->hasFile('photo_interviewee')) {
        //     return $request;
        // }
        // return 'not working';
        $files = [
            'hoh_nic_photo',
            'inter_nic_photo',
            'inter_nic_photo_owner',
            'water_point_photo',
            'access_sanitation_photo',
            'access_education_photo',
            'access_health_photo',
            'access_road_photo',

            'community_center_photo',
            'photo_interviewee',
            'photo_house_building',
            'photo_house_door',
            'photo_enovirment',
            'photo_other'

        ];
        // contains multiple files
        $multipleFiles = [
            'type_return_document_photos',
            'house_document_photos',
            'house_problems_area_photos',
        ];
        $data = $request->except(array_merge($files, $multipleFiles));
         foreach ($data as $key => $value) {
            if ($data[$key] == "null") {
                $data[$key] = "";
            }
        }
        foreach ($files as $key => $value) {
            
            if ($request->hasFile($value) && $request->file($value)->isValid()) {
                $uuidPrefix = Str::uuid();
                $get_file = $request->file($value)->storeAs('kobo-attachments', $this->getFileName($uuidPrefix, $request->file($value)));
                $data[$value] = $get_file;
            }
        }

        if ($request->hasFile('type_return_document_photos')) {
        $data['type_return_document_photos'] = $this->storeArrayFile('type_return_document_photos', $request);
        }
        if ($request->hasFile('house_document_photos')) {
        $data['house_document_photos'] =  $this->storeArrayFile('house_document_photos', $request);
        }
        if ($request->input('house_problems_area_photos')) {
            $data['house_problems_area_photos'] =  $this->storeArrayFileWithTitle('house_problems_area_photos', $request);
            }

        return   $result = $this->parser->parseAndReturn($data);
        if ($result['success'] === 'true') {
            return response()->json(["message" => 'Successfully added!'], 201);
        }
        return response()->json(["message" => $result['error']], 500);

    
    }

    public static function getFileName($prefix, $file) {
        return  $prefix.'-'. \Carbon\Carbon::now()->format('Y-m-d-H-i-s-v') . '.' . $file->getClientOriginalExtension();
    }

    public function storeArrayFile(string $name, Request $request): array
    {
        $storedFiles = [];
        foreach ($request->file($name) as $file) {
                $uuidPrefix = Str::uuid();
                $path = $file->storeAs(
                    'kobo-attachments',
                    $this->getFileName($uuidPrefix, $file)
                );
                $storedFiles[] = $path;
            
        }
        return $storedFiles;
    }

    public function storeArrayFileWithTitle(string $name, Request $request): array
    {
        $groupItems = $request->input($name); // usually the text part
        $files = $request->file($name); // uploaded files (if any)
    
        $storedFiles = [];
    
        foreach ($groupItems as $key => $row) {
            $storedFiles[$key]['current_house_problem_title'] = $row['current_house_problem_title'];
    
            $file = $files[$key]['current_house_problem_photo'] ?? null;
    
                $uuidPrefix = Str::uuid();
                $path = $file->storeAs(
                    'kobo-attachments',
                    $this->getFileName($uuidPrefix, $file)
                );
                $storedFiles[$key]['current_house_problem_photo'] = $path;
        
        }
    
        return $storedFiles;
    }

    public function downloadProfile($id) {
         $submission = Submission::with(['sourceInformation', 'familyInformation', 'headFamily', 'idp', 'returnee', 'interviewwee', 'photoSection', 'houseLandOwnership'])->find($id);
         $form = Form::find($submission->dm_form_id);
         $dataObject = json_decode($form->raw_schema);
         $survey = $dataObject->asset->content->survey;
         $choices = $dataObject->asset->content->choices;
         $location = [];


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
            // if (isset($value->name) && $value->name === $submission->sourceInformation->kbl_guzar_number) {
            //     if (isset($value->label[0])) {
            //         $location['guzar'] = $value->label[0];
            //     }
            // }
            if ($submission->sourceInformation->province_code) {

                    $location['province_code'] = $submission->sourceInformation->province_code;
 
            }
            if ($submission->sourceInformation->city_code) {

                $location['city_code'] = $submission->sourceInformation->city_code;
            }
            if ($submission->sourceInformation->district_code) {

                $location['district_code'] = $submission->sourceInformation->district_code;
            }

            // if ($submission->sourceInformation->kbl_guzar_number) {

            //     $location['guzar'] = $submission->sourceInformation->kbl_guzar_number;
            // }
     
            if (isset($value->name) && $value->name === $submission->sourceInformation->kbl_guzar_number) {
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
            }elseif ($submission->status === 'returnee') {
                if (isset($value->name) && $value->name === $submission->returnee->year_returnee) {
                    if (isset($value->label[1])) {
                        $location['year'] = $value->label[1];
                    }
                }
            } 

            if (isset($value->name) && $value->name === $submission->houseLandOwnership->type_tenure_document) {
                if (isset($value->label[1])) {
                    $location['ownership_type'] = $value->label[1];
                }
            }

        }

       $map_path = $this->getPath($location);
        $location['map_image'] = $map_path;
        $html = View::make('pdf.template', [
            'submission' => $submission,
            'location' => $location,
            'choices' => $choices,
        ])->render();

        $mpdf = new Mpdf([
            'format' => 'A4',
            'mode' => 'utf-8',
            'default_font' => 'dejavusans',
            'default_font_size' => 9,
            'directionality' => 'rtl', // Important for RTL
        ]);

        $mpdf->WriteHTML($html);
        return response($mpdf->Output('', 'S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="report.pdf"');
    
        
        return $submission;

    }

    public function addAsBeneficairy(Request $request) {
        $project = Project::find($request->project_id);
        $project->submissions()->syncWithoutDetaching($request->submissions);
        return response()->json(["message" => "Successfully added!", "data" => $project->load('submissions')], 201);
    }

    public function removeAsBeneficairy(Request $request) {
        $project = Project::find($request->project_id);
        $project->submissions()->detach($request->submissions);
        return response()->json(["message" => "Successfully removed!", "data" => $project->load('submissions')], 201);
    }

    public function downloadExcel(Request $request) {
        // return $request;
        $fields = [];
        foreach ($request->selectedColumns as $key => $value) {
            array_push($fields, $this->filterable[$value]);
        }

        $groupedFields = [];

        foreach ($fields as $field) {
            if (str_contains($field, '__')) {
                [$relation, $column] = explode('__', $field, 2);
                $groupedFields[$relation][] = $column;
            } else {
                 $groupedFields['submission'][] = $field;
            }
        }

        $query = Submission::query();

        // Handle Submission fields
        $submissionFields = $groupedFields['submission'] ?? [];
        if (!empty($submissionFields)) {
            $query->select(array_merge(['id'], $submissionFields)); // keep 'id' to join related tables
        } else {
            $query->select('id');
        }

            // Handle related table fields
    foreach ($groupedFields as $relation => $columns) {
        if ($relation === 'submission') continue;

        $foreignKey = 'submission_id'; // change if your FK is different
        $query->with([$relation => function ($q) use ($columns, $foreignKey) {
            $q->select(array_merge([$foreignKey], array_unique($columns)));
        }]);
    }

    $submissions = $query->whereIn('id', $request->selects)->get();
    $form = Form::find(1);
    $dataObject = json_decode($form->raw_schema);
    $survey = $dataObject->asset->content->survey;
    $choices = $dataObject->asset->content->choices;

    $header = [];
    $result = $submissions->map(function ($submission, $index) use ($fields, $survey, $choices, &$header) {
        $flat = [];
        

        foreach ($fields as $key => $field) {
            if (str_contains($field, '__')) {
                [$relation, $column] = explode('__', $field, 2);

                $flat[$column] = $this->getSurvey($survey, $choices, $column, ($submission->$relation->$column ?? null));
                if ($index === 0) {
                    array_push($header, $this->getHeader($survey, $column));
                }

            } else {
                $flat[$field] = $this->getSurvey($survey, $choices,  $field, $submission->$field ?? null);
                if ($index === 0) {
                    array_push($header, $this->getHeader($survey, $field));
                }

            }
        }

        return $flat;
    });

    return Excel::download(new SubmissionsExport($result, $request->project ? $request->project['label'] : now()->format('Y-m-d'), $header ), now()->format('Y-m-d') . 'submissions.xlsx');
    }

    function getSurvey( $survey, $choices, $name, $value) {
        foreach ($survey as $item) {

            if ($item->type === "select_one" || $item->type === "select_multiple") {
                if ($item->name === $name) {
                    return $this->getLabel($choices, $value);
                }
            }
            
        }
        return $value;
    }

    function getHeader($survey, $column) {
        foreach ($survey as $item) {
            if ($item->type === 'end_group') {
                continue;
            }
    
            if (isset($item->name) && $item->name === $column) {
                if (isset($item->label)) {
                    return is_array($item->label) ? $item->label[0] : $item->label;
                }
            }
        }
    
        return $column;
    }

    function getLabel( $choices, $value) {
        foreach ($choices as $item) {
            if ($item->name === $value) {
                if (isset($item->label[0])) {
                    return $item->label[0];

                }
            }
        }
        return $value;
    }

    public function moveToArchive(Request $request) {
        foreach ($request->selects as $key => $value) {
            $this->archive->archiveSubmission($value, 1);
        }
        
        // return 
        return response()->json(['message' => 'Successfully Archived.'], 201);
    }

    public function importExcel (Request $request) {
        $schema = json_decode(Form::first()->raw_schema);
        $survey = $schema->asset->content->survey;

        $submission = (new Submission)->getIgnoreIdFillable();
        $submission_labels = [];
        foreach ($submission as $key => $value) {
            foreach ($survey as $key => $val) {
                if (isset($val->name) && $val->name === $value) {
                    array_push($submission_labels, $val);
                    break;
                }
            }
        }

        // $path = public_path('wochtangi_final.xlsx');
    
        // if (!File::exists($path)) {
        //     return response()->json(['error' => 'File not found.'], 404);
        // }
    
        // try {
        //     // For immediate processing (smaller files)
        //     // $import = new MultiTableImport();
        //     // Excel::import($import, $path);
            
        //     // For large files - queue the import
        //     $import = new MultiTableImport();
        //     Excel::import($import, $path);
        //     // Excel::queueImport($import, $path);
            
        //     return response()->json([
        //         'message' => 'Import started successfully. Processing in background.',
        //         'rows_processed' => $import->sheets()[0]->getRowCount() ?? 0
        //     ]);
            
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'error' => 'Import failed',
        //         'message' => $e->getMessage()
        //     ], 500);
        // }
        
        $path = public_path('wochtangi_final.xlsx'); // full path to the file

        if (!File::exists($path)) {
            return 'File not found.';
        }

        Excel::import(new MultiTableImport, $path);
        return 'working';
        return $data[0];
        // Example: dump first sheet
        dd($data[0]);


    }

    private function getPath($location) {
        return 'storage/gis/'.'W_'.$location['province_code'].'-'.$location['city_code'].'-'.$location['district_code'].'-'.$location['guzar'].'-'.$location['block'].'-'.$location['house'].'.jpg';
    }

}
