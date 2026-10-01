<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Suites;

use EidCloud\ArabicEvalBench\Metrics\DerWerScorer;
use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;

/**
 * Evaluates Arabic Diacritization and Tashkeel accuracy using DER and WER.
 */
class DiacritizationSuiteEvaluator implements SuiteEvaluatorInterface
{
    private DerWerScorer $scorer;

    public function __construct()
    {
        $this->scorer = new DerWerScorer();
    }

    public function getKey(): string
    {
        return 'diacritization';
    }

    public function getTitle(): string
    {
        return 'Diacritization and Tashkeel (DER / WER)';
    }

    public function evaluate(array $testCases, array $predictions, ErrorTaxonomy $taxonomy): array
    {
        $total = count($testCases);
        $passed = 0;
        $failed = 0;
        $totalDerAcc = 0.0;
        $totalWerAcc = 0.0;
        $details = [];

        foreach ($testCases as $case) {
            $id = $case['id'];
            $expected = $case['expected'];
            $pred = $predictions[$id] ?? null;

            if ($pred === null || !is_string($pred)) {
                $failed++;
                $taxonomy->record('HARAKAT_ERROR', $id, 'Missing or non-string prediction for Tashkeel');
                $details[] = [
                    'id' => $id,
                    'status' => 'FAIL',
                    'reason' => 'Missing prediction',
                    'der' => 1.0,
                    'wer' => 1.0,
                    'accuracy' => 0.0,
                ];
                continue;
            }

            $metrics = $this->scorer->score($pred, $expected);
            $totalDerAcc += $metrics['der_accuracy'];
            $totalWerAcc += $metrics['wer_accuracy'];

            // Pass if DER accuracy is at least 90%
            if ($metrics['der_accuracy'] >= 90.0) {
                $passed++;
                $status = 'PASS';
            } else {
                $failed++;
                $status = 'FAIL';
                $taxonomy->record(
                    'HARAKAT_ERROR',
                    $id,
                    "DER {$metrics['der']} (Acc: {$metrics['der_accuracy']}%), WER {$metrics['wer']}",
                    ['predicted' => $pred, 'expected' => $expected]
                );
            }

            $details[] = [
                'id' => $id,
                'status' => $status,
                'der' => $metrics['der'],
                'wer' => $metrics['wer'],
                'der_accuracy' => $metrics['der_accuracy'],
                'wer_accuracy' => $metrics['wer_accuracy'],
                'accuracy' => $metrics['der_accuracy'],
            ];
        }

        $avgDerAcc = $total > 0 ? round($totalDerAcc / $total, 2) : 0.0;
        $avgWerAcc = $total > 0 ? round($totalWerAcc / $total, 2) : 0.0;
        // Overall accuracy based on Tashkeel character accuracy
        $accuracy = $avgDerAcc;
        $f1 = round($accuracy / 100.0, 4);

        return [
            'suite' => $this->getKey(),
            'title' => $this->getTitle(),
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'accuracy' => $accuracy,
            'der_accuracy' => $avgDerAcc,
            'wer_accuracy' => $avgWerAcc,
            'f1' => $f1,
            'details' => $details,
        ];
    }
}
