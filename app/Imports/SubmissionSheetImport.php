<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Modules\DataManagement\Models\Submission;
use Modules\DataManagement\Models\Form;
use Carbon\Carbon;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

// ✅ Disable header formatting (keep labels exactly as in Excel)
HeadingRowFormatter::default('none');

class SubmissionSheetImport implements ToModel, WithHeadingRow, WithChunkReading, WithLimit
{
    public function model(array $row)
    {
        try {
            logger()->info("Processing row:", $row);

            $schema = json_decode(Form::first()->raw_schema);
            $survey = $schema->asset->content->survey ?? [];
            $choices = $schema->asset->content->choices ?? [];
            
            $submission = (new Submission)->getIgnoreIdFillable();
            $submission_labels = [];
            
            foreach ($submission as $submissionKey => $fieldName) {
                foreach ($survey as $surveyItem) {
                    if (!isset($surveyItem->name, $surveyItem->label[0])) {
                        continue;
                    }

                    if ($surveyItem->name === $fieldName && isset($row[$surveyItem->label[0]])) {
                        $labelValue = $row[$surveyItem->label[0]];

                        if ($surveyItem->type === 'select_one') {
                            foreach ($choices as $choice) {
                                if ($choice->label[0] === $labelValue) {
                                    $submission_labels[] = [$surveyItem->name => $choice->name];
                                    break;
                                }
                            }
                        } else {
                            $submission_labels[] = [$surveyItem->name => $labelValue];
                        }

                        break;
                    }
                }
                logger()->info("Processing rowdddddddddddd:", $submission_labels);
            }

            $submission_labels[] = ['today' => Carbon::today()->toDateString()];

            return new Submission($submission_labels);
        } catch (\Exception $e) {
            logger()->error("Error importing row: " . $e->getMessage());
        }
    }

    public function chunkSize(): int
    {
        return 1;
    }

    public function limit(): int
    {
        return 5;
    }
}
