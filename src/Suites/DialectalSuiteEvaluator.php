<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Suites;

use EidCloud\ArabicEvalBench\Metrics\AccuracyF1Scorer;
use EidCloud\ArabicEvalBench\Metrics\ArabicNormalizer;
use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;

/**
 * Evaluates Arabic dialectal comprehension and Classical Arabic nuances.
 */
class DialectalSuiteEvaluator implements SuiteEvaluatorInterface
{
    private AccuracyF1Scorer $scorer;

    public function __construct()
    {
        $this->scorer = new AccuracyF1Scorer();
    }

    public function getKey(): string
    {
        return 'dialectal';
    }

    public function getTitle(): string
    {
        return 'Classical Arabic & Dialectal Nuance';
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
            $expectedMeaning = $case['expected_meaning'];
            $pred = $predictions[$id] ?? null;

            if ($pred === null || !is_string($pred)) {
                $failed++;
                $taxonomy->record('DIALECT_CONFUSION', $id, 'Missing dialect prediction');
                $details[] = [
                    'id' => $id,
                    'status' => 'FAIL',
                    'reason' => 'Missing prediction',
                    'accuracy' => 0.0,
                    'f1' => 0.0,
                ];
                continue;
            }

            $f1Result = $this->scorer->computeF1($pred, $expectedMeaning);
            $em = $this->scorer->computeExactMatch($pred, $expectedMeaning);

            // If exact match or high F1 (>= 0.65 due to paraphrasing), accept as pass
            $isPass = ($em === 100.0) || ($f1Result['f1'] >= 0.65);
            $itemScore = max($em, $f1Result['f1'] * 100.0);

            $totalAccuracy += $itemScore;
            $totalF1 += $f1Result['f1'];

            if ($isPass) {
                $passed++;
                $status = 'PASS';
            } else {
                $failed++;
                $status = 'FAIL';
                $taxonomy->record(
                    'DIALECT_CONFUSION',
                    $id,
                    "Low dialectal alignment (F1: {$f1Result['f1']}) for {$case['dialect']}",
                    ['predicted' => $pred, 'expected' => $expectedMeaning]
                );
            }

            $details[] = [
                'id' => $id,
                'dialect' => $case['dialect'],
                'status' => $status,
                'f1' => $f1Result['f1'],
                'accuracy' => round($itemScore, 2),
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
