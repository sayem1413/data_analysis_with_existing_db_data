<?php

namespace App\Http\Controllers;

use App\Models\DesiredJob;
use App\Models\DesiredSkill;

class ManipulateDbTableController extends Controller
{
    protected int $strongMatch = 95;
    protected int $partialMatch = 80;

    public function updateDesiredJobsTable()
    {
        ini_set('memory_limit', '512M');

        $report = $this->analyze();

        /* dd(
            $report[0]
        ); */

        $parentMatchCount = 0;
        $parentNotMatchCount = 0;
        $childMatchCount = 0;
        $childNotMatchCount = 0;

        foreach ($report as $item) {
            $parentCategoryId = null;
            $parentData = $item['parent'];
            $childrens = $item['children'];
            // dd($item['category'], $parentData['db_title'], $parentData['score'], $this->isSafe($item['category'], $parentData['db_title'], $parentData['score']));
            if ($this->isSafe($item['category'], $parentData['db_title'], $parentData['score']) && $parentData['status'] == 'Strong Match' && $parentData['db_id']) {
                $parentCategoryId = $parentData['db_id'];
                $parentMatchCount++;
            } else {
                $parentCategory = DesiredSkill::create([
                    'title' => $parentData['db_title'],
                    'title_bn' => $parentData['db_title'],
                ]);
                $parentCategoryId  = $parentCategory->id;
                $parentNotMatchCount++;
            }

            if ($parentCategoryId || true) {
                foreach ($childrens as $children) {
                    if ($this->isSafe($children['csv'], $children['db_title'], $children['score']) && $children['status'] == 'Strong Match' && $children['db_id']) {
                        DesiredSkill::where('id', $children['db_id'])->update([
                            'parent_id' => $parentCategoryId,
                            'active_status' => 'Active'
                        ]);
                        $childMatchCount++;
                    } else {
                        DesiredSkill::create([
                            'title' => $children['db_title'],
                            'title_bn' => $children['db_title'],
                            'parent_id' => $parentCategoryId,
                            'active_status' => 'Active'
                        ]);
                        $childNotMatchCount++;
                    }
                }
            }
        }

        dd(
            'Parent Found = ' . $parentMatchCount,
            'Parent Created = ' . $parentNotMatchCount,
            'Child Updated = ' . $parentNotMatchCount,
            'Child Created = ' . $childNotMatchCount,
        );
    }

    /**
     * Core data analysis (read-only)
     */
    private function analyze(): array
    {
        $csvPath = storage_path('app/files/jobs.csv');

        if (!file_exists($csvPath)) {
            return [];
        }

        $rows = $this->readCsv($csvPath);

        $dbIndex = DesiredSkill::all()->map(fn($job) => [
            'id' => $job->id,
            'title' => $job->title,
            'norm'  => $this->normalize($job->title),
            'words' => $this->tokens($job->title),
        ])
            ->toArray();

        $grouped = collect($rows)->groupBy('Category');

        $report = [];

        foreach ($grouped as $csv_category => $items) {

            [$parentMatchId, $parentMatch, $parentScore] = $this->bestMatch($csv_category, $dbIndex);

            $children = [];

            foreach ($items as $row) {
                $child = trim($row['Title']);

                [$childMatchId, $childMatch, $childScore] = $this->bestMatch(trim($child), $dbIndex);

                $children[] = [
                    'csv'    => $child,
                    'db_id'  => $childMatchId,
                    'db_title'  => $childMatch,
                    'match'  => $childMatch,
                    'score'  => $childScore,
                    'status' => $this->status($childScore),
                ];
            }

            $report[] = [
                'category' => $csv_category,
                'parent' => [
                    'db_id'  => $parentMatchId,
                    'db_title'  => $parentMatch,
                    'match'  => $parentMatch,
                    'score'  => $parentScore,
                    'status' => $this->status($parentScore),
                ],
                'children' => $children,
            ];
        }

        return $report;
    }

    /**
     * Hybrid fuzzy matcher (BEST PRACTICE)
     */
    private function bestMatch(string $needle, array $dbIndex): array
    {
        $needleNorm  = $this->normalize($needle);
        $needleWords = $this->tokens($needle);

        $bestTitle = null;
        $bestScore = 0;

        foreach ($dbIndex as $item) {

            $jaccard = $this->jaccard($needleWords, $item['words']);

            similar_text($needleNorm, $item['norm'], $charPercent);

            $score = max(round($jaccard), round($charPercent));

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $item['id'];
                $bestTitle = $item['title'];
            }
        }

        return [$bestId, $bestTitle, $bestScore];
    }

    private function jaccard(array $a, array $b): int
    {
        if (!$a || !$b) return 0;

        $intersection = array_intersect($a, $b);
        $union = array_unique(array_merge($a, $b));

        return round((count($intersection) / count($union)) * 100);
    }

    private function normalize(string $v): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', trim($v)));
    }

    private function tokens(string $v): array
    {
        $v = strtolower(preg_replace('/[^a-z0-9\s]/i', '', $v));
        return array_values(array_filter(explode(' ', $v)));
    }

    private function status(int $p): string
    {
        return $p >= $this->strongMatch
            ? 'Strong Match'
            : ($p >= $this->partialMatch ? 'Partial Match' : 'No Match');
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        $header = null;

        if (($h = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($h, 1000, ',')) !== false) {
                if (!$header) {
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                    $header = $data;
                    continue;
                }
                $rows[] = array_combine($header, $data);
            }
            fclose($h);
        }

        return $rows;
    }

    private function isSafe(string $csv, ?string $db, int $score): bool
    {
        if (!$db) return false;

        return $score >= $this->strongMatch &&
            $this->normalize($csv) === $this->normalize($db);
    }
}
