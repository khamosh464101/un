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
use Modules\DataManagement\Models\SubmissionStatus;
use Modules\DataManagement\Services\CreateSubmissionParser;
use Modules\DataManagement\Services\FilterableService;
use Modules\DataManagement\Services\QueryService;
use Modules\DataManagement\Services\ArchiveService;
use Modules\Projects\Models\Project;
use Mpdf\Mpdf;
use App\Imports\MultiTableImport;
use App\Models\Setting;

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;

use Storage;

class SubmissionController
{
    protected $parser;
    protected $filterable;
    protected $query;
    protected $archive;

    public function __construct(
        CreateSubmissionParser $submissionParser, 
        FilterableService $filterable,
        QueryService $query,
        ArchiveService $archive
        )
    {
        $this->parser = $submissionParser;
        $this->filterable = $filterable->getFilterable();
        $this->query = $query->getQuery();
        $this->archive = $archive;
    }

    public function index(Request $request) {
        $query = Submission::with($this->query);
        if ($request->project_id) {
            $query->whereHas('projects', function ($q) use ($request) {
                $q->where('projects.id', $request->project_id);
            });
        }
        $this->getSearchData($query, $request);
        $data = $query->paginate(8);

        return ["data" => $data, "filterable" => $this->filterable, "statuses" => SubmissionStatus::select('id as value', 'title as label')->get(), "projects" => Project::select("id as value", "title as label")->get()];
    }

    public function getForm() {
        
        return response()->json(Form::first());
    }

    public function store (Request $request) {
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
                $get_file_clean = str_replace('kobo-attachments/', '', $get_file);
                $data[$value] = $get_file_clean;
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
                $path_clean = str_replace('kobo-attachments/', '', $path);
                $storedFiles[] = $path_clean;
            
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
                $path_clean = str_replace('kobo-attachments/', '', $path);

                $storedFiles[$key]['current_house_problem_photo'] = $path_clean;
        
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

        $si = $submission->sourceInformation;
        $name = $si->province_code.'-'.$si->city_code.'-'.$si->city_code.'-'.$si->district_code.'-'.$location['guzar'].'-'.$location['block'].'-'.$location['house'];
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('', 'S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'. $name .'.pdf"');
    
        
        return $submission;

    }

    public function addAsBeneficairy(Request $request) {
        $project = Project::find($request->project_id);
        $submissions = $request->submissions;
        if ($request->selectAll) {
            $query = Submission::query();
            $this->getSearchData($query, $request);
            $submissions = $query->pluck('id')->toArray();
        }
        $project->submissions()->syncWithoutDetaching($submissions);
        return response()->json(["message" => "Successfully added!", "data" => $project->load('submissions')], 201);
    }

    public function removeAsBeneficairy(Request $request) {
        $project = Project::find($request->project_id);
        $submissions = $request->submissions;
        if ($request->selectAll) {
            $query = Submission::query();
            $this->getSearchData($query, $request);
            $submissions = $query->pluck('id')->toArray();
        }
        $project->submissions()->detach($submissions);
        return response()->json(["message" => "Successfully removed!", "data" => $project->load('submissions')], 201);
    }

    public function downloadExcel(Request $request) {
        
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

        $submissions;
        if ($request->selectAll) {
            $this->getSearchData($query, $request);
            $submissions = $query->get();
        } else {
            $submissions = $query->whereIn('id', $request->selects)->get();
        }
        
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
    function getSearchData($query, $request) {
        foreach ($request->search as $key => $field) {
            if ($field) {
                if (Str::contains($key, '__') ) {
                    [$relation, $column] = explode('__', $key, 2);

                    $query->whereHas($relation, function ($q) use ($column, $field) {
                        $q->where($column, $field);
                    });
                } else {
                    $query->where($key, $field);
                }
            }
        }
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
        $ids = $request->selects;
        if ($request->selectAll === true) {
            $query = Submission::query();
            $this->getSearchData($query, $request);
            $ids = $query->pluck('id')->toArray();
        } 
        foreach ($ids as $key => $value) {
            $this->archive->archiveSubmission($value, 1);
        }
        return response()->json(['message' => 'Successfully Archived.'], 201);
    }

    public function changeStatus(Request $request) {
        $ids = $request->selects;
        if ($request->selectAll === true) {
            $query = Submission::query();
            $this->getSearchData($query, $request);
            $ids = $query->pluck('id')->toArray();
        } 
        Submission::whereIn('id', $ids)->update([
            'submission_status_id' => $request->status['value'],
        ]);
        return $request;
    }

    public function importExcel (Request $request) {
        // return Setting::where('key', 'kobo_token')->first()->value;
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
        
        $path = public_path('wochtangi_final.xlsx'); // full path to the file

        if (!File::exists($path)) {
            return 'File not found.';
        }
        try {
            Excel::import(new MultiTableImport, $path);
    
            return response()->json(['message' => 'Import successful'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
        return 'working';
        return $data[0];
        // Example: dump first sheet
        dd($data[0]);


    }

    private function getPath($location) {
        return 'storage/gis/'.'W_'.$location['province_code'].'-'.$location['city_code'].'-'.$location['district_code'].'-'.$location['guzar'].'-'.$location['block'].'-'.$location['house'].'.jpg';
    }

    public function destroy($id) {
        $submission = Submission::find($id);
        if (!$submission) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $headFamily = $submission->headFamily;
        if ($headFamily) {
            $headFamily->delete();
        }

        $interviewwee = $submission->interviewwee;
        if ($interviewwee) {
            $interviewwee->delete();
        }
        foreach ($submission->returnee?->typeReturnDocumentPhoto ?? [] as $value) {
            $value->delete();
        }
        foreach ($submission->houseLandOwnership?->landOwnershipDocument ?? [] as $key => $value) {
            $value->delete();
        }
        $houseLandOwnership = $submission->houseLandOwnership;
        if ($houseLandOwnership) {
            $houseLandOwnership->delete();
        }
        foreach ($submission->houseCondition?->houseProblemAreaPhoto ?? [] as $key => $value) {
            $value->delete();
        }
        $accessBasicService = $submission->accessBasicService;
        if ($accessBasicService) {
            $accessBasicService->delete();
        }
        $communityAvailability = $submission->communityAvailability;
        if ($communityAvailability) {
            $communityAvailability->delete();
        }
        $photoSection = $submission->photoSection;
        if ($photoSection) {
            $photoSection->delete();
        }
        $submission->delete();

        return response()->json(['message' => 'Record deleted successfully'], 201);
    }

}
