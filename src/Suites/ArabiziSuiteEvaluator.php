<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Suites;

use EidCloud\ArabicEvalBench\Metrics\AccuracyF1Scorer;
use EidCloud\ArabicEvalBench\Metrics\ArabicNormalizer;
use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;

/**
 * Evaluates Arabizi (Franco-Arabic) normalization & translation fidelity into Arabic.
 */
class ArabiziSuiteEvaluator implements SuiteEvaluatorInterface
{
    private AccuracyF1Scorer $scorer;

    public function __construct()
    {
        $this->scorer = new AccuracyF1Scorer();
    }

    public function getKey(): string
    {
        return 'arabizi';
    }

    public function getTitle(): string
    {
        return 'Arabizi / Franco-Arabic Normalization & Fidelity';
    }

    public function evaluate(array $testCases, array $predictions, ErrorTaxonomy $taxonomy): array
    {
        $total = count($testCases);
        $passed = 0;
        $failed = 0;
        $totalF1 = 0.0;
        $totalAccuracy = 0.0;
        $details = [];

        foreach ($testCases as $case) {
            $id = $case['id'];
            $expected = $case['expected_arabic'];
            $keywords = $case['keywords'] ?? [];
            $pred = $predictions[$id] ?? null;

            if ($pred === null || !is_string($pred)) {
                $failed++;
                $taxonomy->record('ARABIZI_MISMATCH', $id, 'Missing Arabizi normalization prediction');
                $details[] = [
                    'id' => $id,
                    'status' => 'FAIL',
                    'reason' => 'Missing prediction',
                    'accuracy' => 0.0,
                    'f1' => 0.0,
                ];
                continue;
            }

            $em = $this->scorer->computeExactMatch($pred, $expected);
            $f1Result = $this->scorer->computeF1($pred, $expected);

            // Check if key Arabic semantic words are recovered
            $normPred = ArabicNormalizer::normalize($pred);
            $matchedKeywords = 0;
            foreach ($keywords as $kw) {
                if (str_contains($normPred, ArabicNormalizer::normalize($kw))) {
                    $matchedKeywords++;
                }
            }
            $keywordRatio = count($keywords) > 0 ? ($matchedKeywords / count($keywords)) : 1.0;

            // Pass condition: high F1 (>= 0.70) or exact match or 100% keywords matched with decent F1
            $isPass = ($em === 100.0) || ($f1Result['f1'] >= 0.70) || ($keywordRatio >= 0.9 && $f1Result['f1'] >= 0.50);
            $score = max($em, $f1Result['f1'] * 100.0);

            $totalAccuracy += $score;
            $totalF1 += $f1Result['f1'];

            if ($isPass) {
                $passed++;
                $status = 'PASS';
            } else {
                $failed++;
                $status = 'FAIL';
                $taxonomy->record(
                    'ARABIZI_MISMATCH',
                    $id,
                    "Arabizi translation mismatch (F1: {$f1Result['f1']}, keywords: {$matchedKeywords}/" . count($keywords) . ")",
                    ['predicted' => $pred, 'expected' => $expected]
                );
            }

            $details[] = [
                'id' => $id,
                'arabizi' => $case['arabizi'],
                'status' => $status,
                'f1' => $f1Result['f1'],
                'accuracy' => round($score, 2),
                'keyword_fidelity' => round($keywordRatio * 100, 1),
            ];
        }

        $avgAccuracy = $total > 0 ? round($totalAccuracy / $total, 2) : 0.0;
        $avgF1 = $total > 0 ? round($totalF1 / $total, 4) : 0.0;

        return [
            'suite' => $this->getKey(),
            'title' => $this->getTitle(),
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'accuracy' => $avgAccuracy,
            'f1' => $avgF1,
            'details' => $details,
        ];
    }
}
