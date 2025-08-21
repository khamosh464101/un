<?php

namespace Modules\ArchiveData\Http\Controllers;

use App\Exports\SubmissionsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\DataManagement\Models\Form;
use Modules\ArchiveData\Models\Submission;
use Modules\DataManagement\Services\FilterableService;
use Modules\DataManagement\Services\QueryService;
use Modules\ArchiveData\Services\RestoreArchiveService;
use Modules\Projects\Models\Project;
use Mpdf\Mpdf;
use Storage;
use Carbon\Carbon;

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;


class SubmissionController
{
    protected $filterable;
    protected $restore;
    protected $query;

    public function __construct(
        FilterableService $filterable,
        QueryService $query,
        RestoreArchiveService $restore
        )
    {
        $this->filterable = $filterable->getFilterable();
        $this->query = $query->getQuery();
        $this->restore = $restore;
    }

    public function index(Request $request) {
        
         $query = Submission::with($this->query);
        
        $this->getSearchData($query, $request);

        return ["data" => $query->paginate(8), "filterable" => $this->filterable, "projects" => Project::select("id as value", "title as label")->get()];
    }
    public function getForm() {
        
        return response()->json(Form::first());
    }


    public function downloadProfile($id) {
         $submission = Submission::with(['sourceInformation', 'familyInformation', 'headFamily', 'idp', 'returnee', 'interviewwee', 'photoSection', 'houseLandOwnership'])->find($id);
         $form = Form::find($submission->dm_form_id);
         $dataObject = json_decode($form->raw_schema);
         $survey = $dataObject->asset->content->survey;
         $choices = $dataObject->asset->content->choices;
         $location = [];
         $firstLetter;


        foreach ($choices as $key => $value) {
            if (isset($value->name) && $value->name === $submission->sourceInformation->survey_province) {
                if (isset($value->label[1])) {
                    $location['province'] = $value->label[1];

                }
            }
            if (isset($value->name) && $value->name === $submission->sourceInformation->district_name) {
                if (isset($value->label[1])) {
                    $location['district'] = $value->label[1];
                    if ($value->label[0] === 'Och Tangi') {
                        $firstLetter = 'W';
                    } else {
                        $firstLetter = ucfirst($value->label[0])[0];
                    }

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

            if ($submission?->projects?->first()) {
            $location['folder'] = $submission?->projects?->first()?->google_storage_folder;
            $map_path = $this->getPath($location, $firstLetter);
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
        }
    
        
        return $submission;

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

    private function getSurvey( $survey, $choices, $name, $value) {
        foreach ($survey as $item) {

            if ($item->type === "select_one" || $item->type === "select_multiple") {
                if ($item->name === $name) {
                    return $this->getLabel($choices, $value);
                }
            }
            
        }
        return $value;
    }

    private function getHeader($survey, $column) {
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

    private function getLabel( $choices, $value) {
        foreach ($choices as $item) {
            if ($item->name === $value) {
                if (isset($item->label[0])) {
                    return $item->label[0];

                }
            }
        }
        return $value;
    }

     private function getPath($location, $firstLetter) {
        // $url = Storage::disk('gcs')->url("1/K_01_01_21_08_001_001.jpg");
        $expiration = Carbon::now()->addMinutes(10);
        $signedUrl = Storage::disk('gcs')->temporaryUrl("{$location['folder']}/"."{$firstLetter}_".$location['province_code'].'-'.$location['city_code'].'-'.$location['district_code'].'-'.$location['guzar'].'-'.$location['block'].'-'.$location['house'].'.jpg', $expiration);
        return $signedUrl;
        // return 'storage/gis/'."{$firstLetter}_".$location['province_code'].'-'.$location['city_code'].'-'.$location['district_code'].'-'.$location['guzar'].'-'.$location['block'].'-'.$location['house'].'.jpg';
    }

    public function restore(Request $request) {
        $ids = $request->selects;
        if ($request->selectAll === true) {
            $query = Submission::query();
            $this->getSearchData($query, $request);
            $ids = $query->pluck('id')->toArray();
        } 

        foreach ($ids as $key => $value) {
            $this->restore->restoreSubmission($value, 1);
        }

        // return 
        return response()->json(['message' => 'Successfully restored.'], 201);
    }


    function getSearchData($query, $request) {
        if ($request->project_id) {
            $query->whereHas('projects', function ($q) use ($request) {
                $q->where('projects.id', $request->project_id);
            });
        }
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

  

}
