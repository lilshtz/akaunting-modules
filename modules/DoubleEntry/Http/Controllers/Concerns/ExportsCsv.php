<?php

namespace Modules\DoubleEntry\Http\Controllers\Concerns;

trait ExportsCsv
{
    protected function streamCsv(string $reportName, array $headings, array $rows, ?string $date = null)
    {
        $filename = sprintf('%s-%s.csv', $reportName, $date ?: now()->toDateString());

        return response()->stream(function () use ($headings, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headings);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
