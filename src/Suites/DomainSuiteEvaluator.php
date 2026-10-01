<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Suites;

use EidCloud\ArabicEvalBench\Metrics\AccuracyF1Scorer;
use EidCloud\ArabicEvalBench\Metrics\ArabicNormalizer;
use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;

/**
 * Evaluates Financial, Legal, and Technical Arabic domain comprehension and terminology fidelity.
 */
class DomainSuiteEvaluator implements SuiteEvaluatorInterface
{
    private AccuracyF1Scorer $scorer;

    public function __construct()
    {
        $this->scorer = new AccuracyF1Scorer();
    }

    public function getKey(): string
    {
        return 'domain';
    }

    public function getTitle(): string
    {
        return 'Financial, Legal & Technical Domain Understanding';
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
            $domain = $case['domain'];
            $pred = $predictions[$id] ?? null;

            if ($pred === null) {
                $failed++;
                $taxonomy->record('DOMAIN_TERMINOLOGY', $id, "Missing prediction for {$domain} domain task");
                $details[] = [
                    'id' => $id,
                    'domain' => $domain,
                    'status' => 'FAIL',
                    'reason' => 'Missing prediction',
                    'accuracy' => 0.0,
                    'f1' => 0.0,
                ];
                continue;
            }

            $score = 0.0;
            $f1 = 0.0;

            if (isset($case['expected_terms'])) {
                // Terminology mapping dictionary check
                $expectedTerms = $case['expected_terms'];
                $matched = 0;
                $termCount = count($expectedTerms);

                if (is_array($pred)) {
                    foreach ($expectedTerms as $termKey => $termVal) {
                        if (isset($pred[$termKey])) {
                            $predTerm = ArabicNormalizer::normalize((string)$pred[$termKey]);
                            $expTerm = ArabicNormalizer::normalize((string)$termVal);
                            if (str_contains($predTerm, $expTerm) || str_contains($expTerm, $predTerm)) {
                                $matched++;
                            }
                        }
                    }
                } elseif (is_string($pred)) {
                    $normPred = ArabicNormalizer::normalize($pred);
                    foreach ($expectedTerms as $termVal) {
                        if (str_contains($normPred, ArabicNormalizer::normalize((string)$termVal))) {
                            $matched++;
                        }
                    }
                }

                $score = $termCount > 0 ? round(($matched / $termCount) * 100.0, 2) : 0.0;
                $f1 = round($score / 100.0, 4);
            } elseif (isset($case['expected_keywords'])) {
                // Key concepts presence check
                $keywords = $case['expected_keywords'];
                $predStr = is_array($pred) ? json_encode($pred, JSON_UNESCAPED_UNICODE) : (string)$pred;
                $normPred = ArabicNormalizer::normalize($predStr);
                $matched = 0;
                foreach ($keywords as $kw) {
                    if (str_contains($normPred, ArabicNormalizer::normalize($kw))) {
                        $matched++;
                    }
                }
                $score = count($keywords) > 0 ? round(($matched / count($keywords)) * 100.0, 2) : 0.0;
                $f1 = round($score / 100.0, 4);
            }

            $totalAccuracy += $score;
            $totalF1 += $f1;

            if ($score >= 70.0) {
                $passed++;
                $status = 'PASS';
            } else {
                $failed++;
                $status = 'FAIL';
                $taxonomy->record(
                    'DOMAIN_TERMINOLOGY',
                    $id,
                    "Terminology accuracy below threshold in {$domain} ({$score}%)",
                    ['predicted' => $pred, 'case' => $case]
                );
            }

            $details[] = [
                'id' => $id,
                'domain' => $domain,
                'status' => $status,
                'accuracy' => $score,
                'f1' => $f1,
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
