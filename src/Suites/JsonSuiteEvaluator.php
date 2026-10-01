<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Suites;

use EidCloud\ArabicEvalBench\Metrics\AccuracyF1Scorer;
use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;

/**
 * Evaluates LLM structured JSON output against ground truth schemas and values.
 */
class JsonSuiteEvaluator implements SuiteEvaluatorInterface
{
    private AccuracyF1Scorer $scorer;

    public function __construct()
    {
        $this->scorer = new AccuracyF1Scorer();
    }

    public function getKey(): string
    {
        return 'json';
    }

    public function getTitle(): string
    {
        return 'Structured JSON Extraction';
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
            $expected = $case['expected'];
            $pred = $predictions[$id] ?? null;

            if ($pred === null) {
                $failed++;
                $taxonomy->record('SYNTAX_ERROR', $id, 'Missing prediction for test case');
                $details[] = [
                    'id' => $id,
                    'status' => 'FAIL',
                    'reason' => 'Missing prediction',
                    'accuracy' => 0.0,
                    'f1' => 0.0,
                ];
                continue;
            }

            // Handle string JSON prediction or pre-parsed array
            $parsedPred = $pred;
            if (is_string($pred)) {
                $cleaned = trim($pred);
                // Strip markdown code fences if present
                if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $cleaned, $matches)) {
                    $cleaned = $matches[1];
                }
                $decoded = json_decode($cleaned, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $failed++;
                    $taxonomy->record('SYNTAX_ERROR', $id, 'JSON syntax error: ' . json_last_error_msg());
                    $details[] = [
                        'id' => $id,
                        'status' => 'FAIL',
                        'reason' => 'Invalid JSON output',
                        'accuracy' => 0.0,
                        'f1' => 0.0,
                    ];
                    continue;
                }
                $parsedPred = $decoded;
            }

            if (!is_array($parsedPred)) {
                $failed++;
                $taxonomy->record('SYNTAX_ERROR', $id, 'Prediction is not a valid JSON object/array');
                $details[] = [
                    'id' => $id,
                    'status' => 'FAIL',
                    'reason' => 'Expected JSON object',
                    'accuracy' => 0.0,
                    'f1' => 0.0,
                ];
                continue;
            }

            $scoreResult = $this->scorer->computeJsonF1($parsedPred, $expected);
            $totalAccuracy += $scoreResult['field_accuracy'];
            $totalF1 += $scoreResult['f1'];

            if ($scoreResult['exact_match'] || $scoreResult['field_accuracy'] >= 95.0) {
                $passed++;
                $status = 'PASS';
            } else {
                $failed++;
                $status = 'FAIL';
                $taxonomy->record(
                    'SCHEMA_MISMATCH',
                    $id,
                    "Field match mismatch ({$scoreResult['matched_fields']}/{$scoreResult['total_fields']} fields)",
                    ['predicted' => $parsedPred, 'expected' => $expected]
                );
            }

            $details[] = [
                'id' => $id,
                'status' => $status,
                'field_accuracy' => $scoreResult['field_accuracy'],
                'f1' => $scoreResult['f1'],
                'matched_fields' => $scoreResult['matched_fields'],
                'total_fields' => $scoreResult['total_fields'],
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
