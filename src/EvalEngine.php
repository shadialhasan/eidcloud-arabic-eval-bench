<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench;

use EidCloud\ArabicEvalBench\Datasets\DatasetLoader;
use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;
use EidCloud\ArabicEvalBench\Reports\Scorecard;
use EidCloud\ArabicEvalBench\Suites\ArabiziSuiteEvaluator;
use EidCloud\ArabicEvalBench\Suites\DiacritizationSuiteEvaluator;
use EidCloud\ArabicEvalBench\Suites\DialectalSuiteEvaluator;
use EidCloud\ArabicEvalBench\Suites\DomainSuiteEvaluator;
use EidCloud\ArabicEvalBench\Suites\GrammarSuiteEvaluator;
use EidCloud\ArabicEvalBench\Suites\JsonSuiteEvaluator;
use EidCloud\ArabicEvalBench\Suites\SuiteEvaluatorInterface;
use RuntimeException;

/**
 * Core Evaluation Orchestration Engine for Arabic AI Benchmarking.
 */
class EvalEngine
{
    private DatasetLoader $loader;
    /** @var array<string, SuiteEvaluatorInterface> */
    private array $evaluators = [];

    public function __construct(?DatasetLoader $loader = null)
    {
        $this->loader = $loader ?? new DatasetLoader();
        $this->registerDefaultEvaluators();
    }

    private function registerDefaultEvaluators(): void
    {
        $this->registerEvaluator(new JsonSuiteEvaluator());
        $this->registerEvaluator(new DiacritizationSuiteEvaluator());
        $this->registerEvaluator(new DialectalSuiteEvaluator());
        $this->registerEvaluator(new GrammarSuiteEvaluator());
        $this->registerEvaluator(new ArabiziSuiteEvaluator());
        $this->registerEvaluator(new DomainSuiteEvaluator());
    }

    public function registerEvaluator(SuiteEvaluatorInterface $evaluator): void
    {
        $this->evaluators[$evaluator->getKey()] = $evaluator;
    }

    /**
     * Run evaluation against predictions.
     *
     * @param array<string, mixed> $predictions Keyed by suite key or flat case ID
     * @param array<string>|string $suites
     * @param string $modelName
     * @return Scorecard
     */
    public function evaluate(array $predictions, array|string $suites = 'all', string $modelName = 'custom/model'): Scorecard
    {
        $startTime = microtime(true);
        $datasetMap = $this->loader->loadSuites($suites);
        $taxonomy = new ErrorTaxonomy();
        $suiteResults = [];

        foreach ($datasetMap as $suiteKey => $cases) {
            if (!isset($this->evaluators[$suiteKey])) {
                throw new RuntimeException("No evaluator registered for suite: {$suiteKey}");
            }

            $evaluator = $this->evaluators[$suiteKey];

            // Extract relevant predictions (either nested under suiteKey or flat by ID)
            $suitePreds = [];
            if (isset($predictions[$suiteKey]) && is_array($predictions[$suiteKey])) {
                $suitePreds = $predictions[$suiteKey];
            } else {
                foreach ($cases as $case) {
                    $id = $case['id'];
                    if (isset($predictions[$id])) {
                        $suitePreds[$id] = $predictions[$id];
                    }
                }
            }

            $result = $evaluator->evaluate($cases, $suitePreds, $taxonomy);
            $suiteResults[$suiteKey] = $result;
        }

        $duration = microtime(true) - $startTime;

        return new Scorecard($modelName, $suiteResults, $taxonomy, $duration);
    }

    /**
     * Generate synthetic ground-truth baseline predictions (for dry-run/pipeline testing).
     *
     * @param array<string>|string $suites
     * @return array<string, mixed>
     */
    public function generateGroundTruthPredictions(array|string $suites = 'all'): array
    {
        $datasets = $this->loader->loadSuites($suites);
        $predictions = [];

        foreach ($datasets as $suiteKey => $cases) {
            $predictions[$suiteKey] = [];
            foreach ($cases as $case) {
                $id = $case['id'];
                if ($suiteKey === 'json') {
                    $predictions[$suiteKey][$id] = $case['expected'];
                } elseif ($suiteKey === 'diacritization') {
                    $predictions[$suiteKey][$id] = $case['expected'];
                } elseif ($suiteKey === 'dialectal') {
                    $predictions[$suiteKey][$id] = $case['expected_meaning'];
                } elseif ($suiteKey === 'grammar') {
                    $predictions[$suiteKey][$id] = $case['corrected'];
                } elseif ($suiteKey === 'arabizi') {
                    $predictions[$suiteKey][$id] = $case['expected_arabic'];
                } elseif ($suiteKey === 'domain') {
                    if (isset($case['expected_terms'])) {
                        $predictions[$suiteKey][$id] = $case['expected_terms'];
                    } elseif (isset($case['summary'])) {
                        $predictions[$suiteKey][$id] = $case['summary'];
                    }
                }
            }
        }

        return $predictions;
    }

    /**
     * Get list of loaded evaluators.
     *
     * @return array<string, string>
     */
    public function getAvailableSuites(): array
    {
        $list = [];
        foreach ($this->evaluators as $key => $evaluator) {
            $list[$key] = $evaluator->getTitle();
        }
        return $list;
    }
}
