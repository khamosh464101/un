<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SubmissionsExport implements FromQuery, WithMapping, WithHeadings, WithTitle, WithChunkReading, WithEvents
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    protected $query;
    protected $fields;
    protected $survey;
    protected $choices;
    protected $header;
    protected $title;

    public function __construct($query, $fields, $survey, $choices, $header, $title)
    {
        $this->query   = $query;
        $this->fields  = $fields;
        $this->survey  = $survey;
        $this->choices = $choices;
        $this->header  = $header;
        $this->title   = $title;
    }

    public function query()
    {
        return $this->query;
    }

    public function map($submission): array
    {
        $flat = [];

        foreach ($this->fields as $field) {
            if (str_contains($field, '__')) {
                [$relation, $column] = explode('__', $field, 2);
                $flat[$column] = $this->getSurveyValue(
                    $this->survey,
                    $this->choices,
                    $column,
                    optional($submission->$relation)->$column
                );
            } else {
                $flat[$field] = $this->getSurveyValue(
                    $this->survey,
                    $this->choices,
                    $field,
                    $submission->$field
                );
            }
        }

        return $flat;
    }

    public function headings(): array
    {
        return array_map('strtoupper', $this->header);
    }

    public function title(): string
    {
        return $this->title ?: 'Report';
    }

    public function chunkSize(): int
    {
        return 500; // process 500 rows at a time
    }

    public function registerEvents(): array
    {
        $columnCount = count($this->header);
        $lastColumnLetter = Coordinate::stringFromColumnIndex($columnCount);

        return [
            AfterSheet::class => function (AfterSheet $event) use ($lastColumnLetter) {
                $sheet = $event->sheet;
                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->setCellValue('A1', $this->title);

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
                ]);
            },
        ];
    }

    /**
     * Optional helper for transforming survey data
     */
    protected function getSurveyValue($survey, $choices, $field, $value)
    {
        // you can keep your original getSurvey() logic here
        // for now, just return the raw value
        return $value ?? '';
    }
}
