<?php

namespace App\Http\Controllers;

use App\Models\DesiredJob;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Log;

class AnalysisReportV1Controller extends Controller
{
    protected int $strongMatch = 90;
    protected int $partialMatch = 70;

    public function downloadPdfReportV1()
    {
        ini_set('memory_limit', '512M');
        $report = $this->dataAnalysisWithBestMatchedPercentagePDF();

        $pdf = Pdf::loadView('reports.job-analysis-v1', compact('report'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);

        return $pdf->download('analysis-report-v1-'.time().'.pdf');
    }

    public function dataAnalysisWithBestMatchedPercentagePDF(): array
    {
        $csvPath = storage_path('app/files/jobs.csv');

        if (!file_exists($csvPath)) {
            return [];
        }

        $rows = $this->readCsv($csvPath);
        $dbTitles = DesiredJob::pluck('title')->toArray();
        $grouped = collect($rows)->groupBy('Category');

        $report = [];

        foreach ($grouped as $category => $items) {

            [$parentMatch, $parentPercent] = $this->bestMatch($category, $dbTitles);

            $parentStatus = $parentPercent >= $this->strongMatch
                ? 'Strong Match'
                : ($parentPercent >= $this->partialMatch ? 'Partial Match' : 'No Match');

            $children = [];

            foreach ($items as $row) {
                $child = trim($row['Title']);
                [$childMatch, $childPercent] = $this->bestMatch($child, $dbTitles);

                $children[] = [
                    'csv_title'   => $child,
                    'db_match'    => $childMatch,
                    'percentage'  => $childPercent,
                    'status'      => $childPercent >= $this->strongMatch
                        ? 'Strong Match'
                        : ($childPercent >= $this->partialMatch ? 'Partial Match' : 'No Match'),
                ];
            }

            $report[] = [
                'category'        => $category,
                'parent_match'    => $parentMatch,
                'parent_percent'  => $parentPercent,
                'parent_status'   => $parentStatus,
                'children'        => $children,
            ];
        }

        return $report;
    }

    /**
     * Read CSV into associative array
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        $header = null;

        if (($handle = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$header) {
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                    $header = $data;
                    continue;
                }
                $rows[] = array_combine($header, $data);
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Find best fuzzy match
     */
    private function bestMatch(string $needle, array $haystack): array
    {
        $best = null;
        $bestPercent = 0;

        foreach ($haystack as $dbTitle) {
            similar_text(
                $this->normalize($needle),
                $this->normalize($dbTitle),
                $percent
            );

            if ($percent > $bestPercent) {
                $bestPercent = round($percent);
                $best = $dbTitle;
            }
        }

        return [$best, $bestPercent];
    }

    /**
     * Normalize string for fuzzy matching
     */
    private function normalize(string $value): string
    {
        return strtolower(
            preg_replace('/[^a-z0-9]/i', '', trim($value))
        );
    }

    private function error($text)
    {
        Log::error($text);
    }

    private function info($text)
    {
        Log::info($text);
    }
}
