<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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

        $startRow = 1;
        if ($title) {
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCellsByColumnAndRow(1, 1, count($headers), 1);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $startRow = 3;
        }

        foreach ($headers as $i => $label) {
            $col = $i + 1;
            $sheet->setCellValueByColumnAndRow($col, $startRow, $label);
        }

        $range = $sheet->getCellByColumnAndRow(1, $startRow)->getCoordinate()
            . ':' . $sheet->getCellByColumnAndRow(count($headers), $startRow)->getCoordinate();
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $row = $startRow + 1;
        foreach ($rows as $data) {
            foreach (array_values($data) as $i => $value) {
                $sheet->setCellValueByColumnAndRow($i + 1, $row, $value);
            }
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow > $startRow) {
            $dataRange = 'A' . ($startRow + 1) . ':' . $sheet->getCellByColumnAndRow(count($headers), $lastRow)->getCoordinate();
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
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
