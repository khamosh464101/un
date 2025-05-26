<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class SubmissionsExport implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    protected $data;
    protected $title;
    protected $header;
    public function __construct($data, $title, $header)
    {
        $this->data = $data;
        $this->title = $title;
        $this->header = $header;
    }

    public function headings(): array
    {
        return array_map('strtoupper', $this->header);
    }
        public function collection()
    {
        return collect($this->data);
    }
    
    // public function array(): array
    // {
    //     return $this->data;
    // }

    public function title(): string
    {
        return 'Report'; // sheet tab title
    }

    public function registerEvents(): array
    {
        $columnCount = count($this->header);
        $lastColumnLetter = Coordinate::stringFromColumnIndex($columnCount);

        return [
            AfterSheet::class => function (AfterSheet $event) use ($lastColumnLetter) {
                $sheet = $event->sheet;

                // Insert 1 blank row at the top to make room for title
                $sheet->insertNewRowBefore(1, 1);

                // Merge and set title in row 1
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->setCellValue('A1', $this->title);

                // Style the title
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    ],
                ]);
            },
        ];
    }


    // public function collection()
    // {
    //     return collect($this->data);
    // }
    /**
    * @return \Illuminate\Support\Collection
    */
    // public function collection()
    // {
    //     return Submission::all();
    // }
}
