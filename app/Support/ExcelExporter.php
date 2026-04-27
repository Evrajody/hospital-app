<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExporter
{
    /**
     * Build and stream an XLSX download.
     *
     * @param array<string> $headers Column labels.
     * @param array<array<mixed>> $rows Row data aligned with headers.
     * @param string $filename Download filename without extension.
     * @param string|null $title Optional report title shown on row 1.
     */
    public static function download(array $headers, array $rows, string $filename, ?string $title = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rapport');

        $colCount = max(1, count($headers));
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);

        $startRow = 1;
        if ($title) {
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:' . $lastColLetter . '1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $startRow = 3;
        }

        // En-têtes
        foreach ($headers as $i => $label) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($colLetter . $startRow, $label);
        }

        $headerRange = 'A' . $startRow . ':' . $lastColLetter . $startRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Lignes de données
        $row = $startRow + 1;
        foreach ($rows as $data) {
            foreach (array_values($data) as $i => $value) {
                $colLetter = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue($colLetter . $row, $value);
            }
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow > $startRow) {
            $dataRange = 'A' . ($startRow + 1) . ':' . $lastColLetter . $lastRow;
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Auto-size des colonnes
        for ($col = 1; $col <= $colCount; $col++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
