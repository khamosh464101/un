<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Modules\DataManagement\Services\KoboService;
use Modules\DataManagement\Services\CreateSubmissionParser;
use Modules\DataManagement\Services\FilterableService;
use Modules\DataManagement\Models\Form;
use Modules\DataManagement\Models\Submission;
use Modules\Projects\Models\Project;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\View;
use App\Exports\SubmissionsExport;
use Maatwebsite\Excel\Facades\Excel;


class SubmissionController
{
    protected $parser;
    protected $filterable;

    public function __construct(CreateSubmissionParser $submissionParser, FilterableService $filterable)
    {
        $this->parser = $submissionParser;
        $this->filterable = $filterable->getFilterable();
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
            if ($request->filled($field) && $request->input($field)) {
                
                if (Str::contains($field, '__')) {
                    [$relation, $column] = explode('__', $field, 2);

                    $query->whereHas($relation, function ($q) use ($column, $request, $field) {
                        $q->where($column, $request->input($field));
                    });
                } else {
                    $query->where($field, $request->input($field));
                }
            }
        }

        return ["data" => $query->paginate(8), "filterable" => $this->filterable, "projects" => Project::select("id as value", "title as label")->get()];
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

        $result = $this->parser->parseAndReturn($data);
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
            if (isset($value->name) && $value->name === $submission->sourceInformation->kbl_guzar_number) {
                if (isset($value->label[0])) {
                    $location['guzar'] = $value->label[0];
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

        // return $location;
        $html = View::make('pdf.template', [
            'submission' => $submission,
            'location' => $location,
            'choices' => $choices,
            'title' => 'گزارش آزمایشی',
            'rows' => [
                ['name' => 'عارفه', 'email' => 'arifa@example.com'],
                ['name' => 'فاطمه', 'email' => 'fatima@example.com'],
            ]
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
        return Excel::download(new SubmissionsExport, 'sumbissions.xlsx');
        return $request;
    }

}
