<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Metrics;

/**
 * Categorizes and aggregates errors in Arabic LLM outputs
 * into structured taxonomy buckets.
 */
class ErrorTaxonomy
{
    public const ERROR_TYPES = [
        'SYNTAX_ERROR' => 'JSON Syntax / Parsing Violation',
        'SCHEMA_MISMATCH' => 'Missing or Hallucinated Schema Keys',
        'HARAKAT_ERROR' => 'Tashkeel / Diacritic Misplacement or Case Ending Error',
        'GRAMMAR_INFLECTION' => 'Arabic Morpho-syntactic or Gender/Plural Inflection Error',
        'DIALECT_CONFUSION' => 'Dialectal / Classical Misinterpretation',
        'ARABIZI_MISMATCH' => 'Arabizi Transliteration or Normalization Failure',
        'DOMAIN_TERMINOLOGY' => 'Domain Terminology (Legal/Financial/Technical) Mistranslation',
        'HALLUCINATION' => 'Extraneous Hallucinated Information',
    ];

    /** @var array<string, int> */
    private array $counts = [];

    /** @var array<string, array<int, array{id: string, message: string, detail?: mixed}>> */
    private array $records = [];

    public function __construct()
    {
        foreach (array_keys(self::ERROR_TYPES) as $type) {
            $this->counts[$type] = 0;
            $this->records[$type] = [];
        }
    }

    public function record(string $type, string $testId, string $message, mixed $detail = null): void
    {
        if (!isset($this->counts[$type])) {
            $this->counts[$type] = 0;
            $this->records[$type] = [];
        }

        $this->counts[$type]++;
        $this->records[$type][] = [
            'id' => $testId,
            'message' => $message,
            'detail' => $detail,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return $this->counts;
    }

    /**
     * @return array<string, array<int, array{id: string, message: string, detail?: mixed}>>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * Returns total errors recorded.
     */
    public function getTotal(): int
    {
        return array_sum($this->counts);
    }

    /**
     * Returns formatted distribution with percentages.
     *
     * @return array<string, array{name: string, count: int, percentage: float}>
     */
    public function getDistribution(): array
    {
        $total = $this->getTotal();
        $dist = [];

        foreach ($this->counts as $type => $count) {
            $dist[$type] = [
                'name' => self::ERROR_TYPES[$type] ?? $type,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
            ];
        }

        return $dist;
    }
}
