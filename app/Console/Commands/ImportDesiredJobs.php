<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DesiredJob;

class LogJobSimilarity extends Command
{
    protected $signature = 'log:job-similarity';
    protected $description = 'Log fuzzy similarity of CSV Category and Title against DB titles';

    // Similarity threshold (optional, just for filtering)
    protected $threshold = 0; // 0 = show all matches

    public function handle()
    {
        $csvPath = storage_path('app/files/jobs.csv');

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found at {$csvPath}");
            return;
        }

        // Read CSV
        $rows = $this->readCsv($csvPath);

        dd($rows);

        // Fetch all titles from DB
        $allTitles = DesiredJob::pluck('title')->map(fn($t) => trim($t))->toArray();

        $this->info("Starting similarity check...");

        // Process each row in CSV
        foreach ($rows as $row) {

            // ------------------------
            // Parent category fuzzy match
            // ------------------------
            $category = trim($row['Category']);
            $categoryMatches = [];

            foreach ($allTitles as $dbTitle) {
                similar_text($this->normalize($category), $this->normalize($dbTitle), $percent);
                if ($percent >= $this->threshold) {
                    $categoryMatches[$dbTitle] = round($percent) . '% matched';
                }
            }

            // Log parent category matches
            if (!empty($categoryMatches)) {
                $this->info("'{$category}' => " . json_encode($categoryMatches));
            }

            // ------------------------
            // Child title fuzzy match
            // ------------------------
            $child = trim($row['Title']);
            $childMatches = [];

            foreach ($allTitles as $dbTitle) {
                similar_text($this->normalize($child), $this->normalize($dbTitle), $percent);
                if ($percent >= $this->threshold) {
                    $childMatches[$dbTitle] = round($percent) . '% matched';
                }
            }

            // Log child title matches
            if (!empty($childMatches)) {
                $this->info("'{$child}' => " . json_encode($childMatches));
            }
        }

        $this->info("Similarity check finished.");
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
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]); // remove BOM
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
     * Normalize string for fuzzy matching
     */
    private function normalize(string $str): string
    {
        return strtolower(preg_replace('/[\s\(\)]/', '', trim($str)));
    }
}
