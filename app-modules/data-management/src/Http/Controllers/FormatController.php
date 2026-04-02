<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Projects\Models\Project;
use Modules\DataManagement\Models\ImportFormatFile;
use Modules\DataManagement\Models\ImportFormatMap;

use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Modules\DataManagement\Models\Form;

class FormatController
{
    public function select2() {
        $formats = Project::with('importFormatFiles')
            ->whereHas('importFormatFiles')
            ->get()
            ->map(function($project) {
                return [
                    'value' => $project->id,
                    'label' => $project->title
                ];
            });

        return response()->json($formats, 200);
    }

    public function index(Request $request) {
        $search = $request->search;

        $formats = Project::with('importFormatFiles')
            ->when($search, function($query) use ($search) {
                $query->whereHas('importFormatFiles', function($q) use ($search) {
                    $q->where('excel_file_path', 'like', '%'.$search.'%');
                })->orWhere('title', 'like', '%'.$search.'%');
            })
            ->whereHas('importFormatFiles')
            ->paginate(8);

        return response()->json($formats, 200);
    }
    
    public function create() {
        $files = Storage::disk('excel')->files(); // root of disk

        // Get projects that are NOT attached to any format
        $projects = Project::select('id as value', 'title as label')
            ->whereDoesntHave('importFormatFiles')
            ->get();

        return response()->json([
            'files' => $files,
            'projects' => $projects
        ], 200);
    }

    public function store(Request $request) {
       $format =  ImportFormatFile::create([
            'project_id' => $request->project_id,
            'excel_file_path' => $request->file_path,
        ]);

        return response()->json($format, 201);
    }



    public function edit($id) {

        $project = Project::find($id);
        $project->importFormatFiles;
        $project->importFormatMaps;
        $excel = $project->importFormatFiles()->first()->excel_file_path;

        $path = Storage::disk('excel')->path($excel);
        $result;
        try {

            $result = $this->getExcelIndexColumnAndHeaderMap($path);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);

        }

        $form = Form::where('form_id', $project->kobo_copy_project_id ?? $project->kobo_project_id)?->first();

        if (empty($form)) {
            return response()->json(['message' => `The project doesn't have form.`], 400);
        }

        // Decode raw_schema JSON into array
        $rawSchema = json_decode($form->raw_schema, true);

        // Safely get survey
        $survey = $rawSchema['asset']['content']['survey'] ?? null;
        
        $types = ["integer", "text", "select_one", "date"];
        $filtered = [];

        foreach ($survey as $item) {

            if (
                isset($item['type']) &&
                in_array($item['type'], $types, true)
            ) {
                logger()->info('Processing item: ' . ($item['name'] ?? 'unknown') . ' of type ' . $item['type']);

                $newItem = $item;

                $qpath = $item['$qpath'] ?? null;
                $xpath = $item['$xpath'] ?? null;

                $parts = $qpath
                    ? explode('-', $qpath)
                    : ($xpath ? explode('/', $xpath) : []);

                if ($parts) {
                    array_pop($parts);

                    $labels = array_map(fn($parent) => $this->geLabelByName($parent, $survey), $parts);

                    $newItem['allLabel'] = implode(' / ', $labels) . ' / ' . ($item['label'][0] ?? $item['name']);
                }

                
                $filtered[] = $newItem;
            }
        }


        return response()->json([
            'project' => $project,
            'headers' => $result,
            'form'    => $filtered,
        ], 201);

    }

    public function getExcelIndexColumnAndHeaderMap($path)
    {
        if (!file_exists($path)) {
            throw new \Exception('File not found', 404);
        }

        $rows = Excel::toArray([], $path);

        if (empty($rows) || empty($rows[0][0])) {
            throw new \Exception('No headers found', 400);
        }

        $headerRow = $rows[0][0];

        $result = [];

        foreach ($headerRow as $index => $value) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);

            $result[] = [
                "value" => $columnLetter,
                "label" => $value
            ];
        }

        return $result;
    }

    private function geLabelByName($name, $survey) {
        foreach ($survey as $item) {
            if (isset($item['name']) && $item['name'] === $name) {
                return $item['label'][0] ?? $name;
            }
        }
        return $name; // fallback to name if label not found
    }


    public function destroy($id) {
        $project = Project::find($id);

        if (!$project) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $project->importFormatFIles()->delete();
        $project->importFormatMaps()->delete();

        return response()->json(['message' => 'Record deleted successfully'], 201);   
    }
}
