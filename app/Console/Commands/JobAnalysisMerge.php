<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DesiredJob;
use Illuminate\Support\Facades\DB;

class JobAnalysisMerge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analysis:merge-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analysis and merge desired jobs table with existing data and merge from csv data analysis';

    protected int $strongMatch = 95;
    protected int $partialMatch = 80;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $report = $this->analyze();

        DB::beginTransaction();
        $data = $this->updateTable($report);
        DB::commit();

        $this->info('Parent skill found count => ' . $data['parentMatchCount']);
        $this->info('Parent skill created count => ' . $data['parentNotMatchCount']);
        $this->info('Child skill updated count => ' . $data['childMatchCount']);
        $this->info('Child skill created count => ' . $data['childNotMatchCount']);
    }

    private function updateTable($report)
    {
        $parentMatchCount = 0;
        $parentNotMatchCount = 0;
        $childMatchCount = 0;
        $childNotMatchCount = 0;

        $items = [];

        foreach ($report as $item) {
            $parentCategory = null;
            $parentCategoryId = null;
            $parentData = $item['parent'];
            $childrens = $item['children'];
            if ($this->isSafe($item['category'], $parentData['db_title'], $parentData['score']) && $parentData['status'] == 'Strong Match' && $parentData['db_id']) {
                $parentCategoryId = $parentData['db_id'];
                $parentCategory = DesiredJob::where('id', $parentCategoryId)->first();

                if ($parentCategory && $parentCategory->id == $parentCategoryId) {
                    $parentCategory->active_status = 'Active';
                    $parentCategory->parent_id = NULL;
                    $parentCategory->save();

                    $parentMatchCount++;
                    $this->info('Found Parent job category number - ' . $parentMatchCount . '! Prev. Parent job Id is - ' . $parentCategoryId);
                }
            } else {
                $parentCategory = DesiredJob::create([
                    'title' => $item['category'],
                    'title_bn' => $item['category_bn'] ?? $item['category'],
                    'parent_id' => NULL,
                    'active_status' => 'Active'
                ]);
                $parentCategoryId  = $parentCategory->id;
                $parentNotMatchCount++;
                $items[] = [
                    'id' => $parentCategory->id,
                    'title' => $parentCategory->title,
                    'parent_id' => null,
                    'parent_title' => null,
                ];
                $this->info('Created Parent job category number - ' . $parentNotMatchCount . '! Created Parent job Id is - ' . $parentCategoryId);
            }

            if ($parentCategoryId && !empty($childrens) && count($childrens)) {

                foreach ($childrens as $children) {

                    if ($this->isSafe($children['csv'], $children['db_title'], $children['score']) && $children['status'] == 'Strong Match' && $children['db_id']) {
                        $job = DesiredJob::where('id', $children['db_id'])->first();

                        if ($job && $job->id != $parentCategoryId && $job->id == $children['db_id']) {
                            $job->parent_id = $parentCategoryId;
                            $job->active_status = 'Active';

                            if (!$job?->bmet_reference_code) {
                                $job->bmet_reference_code = $children['bmet_reference_code'];
                            }

                            $job->save();
                            $childMatchCount++;
                            $this->info('Found Child job category number - ' . $childMatchCount . '! Prev. Child job Id is - ' . $job->id);
                        } else if($job && $job->id == $parentCategoryId) {
                            $job = DesiredJob::where('title', $children['csv'])->orderBy('id', 'desc')->first();

                            if ($job && $job->id != $parentCategoryId) {
                                $job->parent_id = $parentCategoryId;
                                $job->active_status = 'Active';

                                if (!$job?->bmet_reference_code) {
                                    $job->bmet_reference_code = $children['bmet_reference_code'];
                                }

                                $job->save();
                                $childMatchCount++;
                                $this->info('Found Child job category number - ' . $childMatchCount . '! Prev. Child job Id is - ' . $job->id);
                            }
                        }
                    } else {
                        $job = DesiredJob::where('title', $children['csv'])->first();

                        if ($job && $job->id != $parentCategoryId) {
                            $job->parent_id = $parentCategoryId;
                            $job->active_status = 'Active';

                            if (!$job?->bmet_reference_code) {
                                $job->bmet_reference_code = $children['bmet_reference_code'];
                            }

                            $job->save();
                            $childMatchCount++;
                            $this->info('Found Child job category number - ' . $childMatchCount . '! Prev. Child job Id is - ' . $job->id);
                        } else {
                            $job = DesiredJob::create([
                                'title' => $children['csv'],
                                'title_bn' => $children['csv'],
                                'parent_id' => $parentCategoryId,
                                'active_status' => 'Active',
                                'bmet_reference_code' => $children['bmet_reference_code'],
                            ]);
                            $childNotMatchCount++;
                            $items[] = [
                                'id' => $job->id,
                                'title' => $job->title,
                                'parent_id' => $parentCategory->id,
                                'parent_title' => $parentCategory->title,
                            ];
                            $this->info('Created Child job category number - ' . $childNotMatchCount . '! Created Child job Id is - ' . $job->id);
                        }
                    }
                }
            }
        }

        $this->handleUnusedJobCategories();

        return [
            'items' => $items,
            'parentMatchCount' => $parentMatchCount,
            'parentNotMatchCount' => $parentNotMatchCount,
            'childMatchCount' => $childMatchCount,
            'childNotMatchCount' => $childNotMatchCount,
        ];
    }

    public function handleUnusedJobCategories()
    {
        $categories = DesiredJob::where('parent_id', 0)->get();

        foreach ($categories as $category) {
            $category->update([
                'parent_id' => $category->id
            ]);
        }

        return true;
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

        $dbIndex = DesiredJob::all()->map(fn($job) => [
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
                    'bmet_reference_code' => $row['BMET_reference_code'] ? (int) $row['BMET_reference_code'] : null
                ];
            }

            $report[] = [
                'category' => $csv_category,
                'category_bn' => $items[0]['Category_BN'] ?? $items[0]['Category BN'], // Only for new parent skills
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
