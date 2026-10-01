<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Suites;

use EidCloud\ArabicEvalBench\Metrics\AccuracyF1Scorer;
use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;

/**
 * Evaluates Arabic grammar, morphology, declension (I'rab), and syntactic concord.
 */
class GrammarSuiteEvaluator implements SuiteEvaluatorInterface
{
    private AccuracyF1Scorer $scorer;

    public function __construct()
    {
        $this->scorer = new AccuracyF1Scorer();
    }

    public function getKey(): string
    {
        return 'grammar';
    }

    public function getTitle(): string
    {
        return 'Arabic Grammar, Morphology & Syntactic Reasoning';
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
            $corrected = $case['corrected'];
            $pred = $predictions[$id] ?? null;

            if ($pred === null || !is_string($pred)) {
                $failed++;
                $taxonomy->record('GRAMMAR_INFLECTION', $id, 'Missing grammar correction prediction');
                $details[] = [
                    'id' => $id,
                    'status' => 'FAIL',
                    'reason' => 'Missing prediction',
                    'accuracy' => 0.0,
                    'f1' => 0.0,
                ];
                continue;
            }

            $em = $this->scorer->computeExactMatch($pred, $corrected);
            $f1Result = $this->scorer->computeF1($pred, $corrected);

            // Pass if exact match or F1 >= 0.85 (handles slight particle variation)
            $isPass = ($em === 100.0) || ($f1Result['f1'] >= 0.85);
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
                    'GRAMMAR_INFLECTION',
                    $id,
                    "Grammar error on topic: {$case['topic']} (F1: {$f1Result['f1']})",
                    ['predicted' => $pred, 'expected' => $corrected, 'rule' => $case['irab_rule']]
                );
            }

            $details[] = [
                'id' => $id,
                'topic' => $case['topic'],
                'status' => $status,
                'f1' => $f1Result['f1'],
                'accuracy' => round($score, 2),
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
