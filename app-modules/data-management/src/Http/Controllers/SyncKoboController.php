<?php

namespace Modules\DataManagement\Http\Controllers;
use Modules\DataManagement\Services\KoboService;
use Modules\DataManagement\Services\KoboSubmissionParser;
use Illuminate\Http\Request;
use Modules\DataManagement\Models\Form;
use Modules\DataManagement\Models\Submission;

class SyncKoboController
{
    protected $kobo;
    protected $parser;

    public function __construct(KoboService $kobo, KoboSubmissionParser $submissionParser)
    {
        $this->kobo = $kobo;
        $this->parser = $submissionParser;
    }

    public function addFormToDb() {
        $forms = $this->kobo->getFormDetails();
        Form::create(['raw_schema' => $forms]);
        return response()->json(Form::first());
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
                $this->parser->parseAndReturn($result, $projectId);
            }

            return response()->json(['message' => 'Successfully inserted into the system from kobo.'], 201);

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
    
}
