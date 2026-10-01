<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Suites;

use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;

interface SuiteEvaluatorInterface
{
    /**
     * Unique suite identifier key (e.g. 'json', 'grammar', 'diacritization').
     */
    public function getKey(): string;

    /**
     * Suite human-readable title.
     */
    public function getTitle(): string;

    /**
     * Evaluates predictions against test cases in the suite.
     *
     * @param array<int, array<string, mixed>> $testCases
     * @param array<string, mixed> $predictions Keyed by test case ID
     * @param ErrorTaxonomy $taxonomy Error taxonomy accumulator
     * @return array{
     *   suite: string,
     *   total: int,
     *   passed: int,
     *   failed: int,
     *   accuracy: float,
     *   f1: float,
     *   details: array<int, array<string, mixed>>
     * }
     */
    public function evaluate(array $testCases, array $predictions, ErrorTaxonomy $taxonomy): array;
}
