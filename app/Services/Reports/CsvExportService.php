<?php

namespace App\Services\Reports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public function download(string $filename, array $headers, array $rows, array $footer = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $footer): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->sanitizeRow($headers));

            foreach ($rows as $row) {
                fputcsv($handle, $this->sanitizeRow($row));
            }

            if ($footer !== []) {
                fputcsv($handle, $this->sanitizeRow($footer));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function sanitizeRow(array $row): array
    {
        return array_map(fn ($cell) => $this->sanitizeCell($cell), $row);
    }

    private function sanitizeCell(mixed $cell): mixed
    {
        if ($cell === null) {
            return '';
        }

        if (is_int($cell) || is_float($cell)) {
            return $cell;
        }

        if (is_string($cell) && is_numeric($cell)) {
            return $cell + 0;
        }

        $value = (string) $cell;

        if (preg_match('/^[=+@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
