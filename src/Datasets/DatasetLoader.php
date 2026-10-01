<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Datasets;

use RuntimeException;

/**
 * Loads and validates evaluation benchmark datasets.
 */
class DatasetLoader
{
    private string $dataDir;

    public const SUITES = [
        'json' => 'json_extraction.json',
        'diacritization' => 'diacritization.json',
        'dialectal' => 'dialectal_nuance.json',
        'grammar' => 'grammar_morphology.json',
        'arabizi' => 'arabizi_normalization.json',
        'domain' => 'domain_understanding.json',
    ];

    public function __construct(?string $dataDir = null)
    {
        $this->dataDir = $dataDir ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data';
    }

    /**
     * Load a specific benchmark dataset by suite name.
     *
     * @param string $suite One of: json, diacritization, dialectal, grammar, arabizi, domain
     * @return array<int, array<string, mixed>>
     */
    public function loadSuite(string $suite): array
    {
        $normalizedSuite = strtolower(trim($suite));
        if (!isset(self::SUITES[$normalizedSuite])) {
            throw new RuntimeException("Unknown evaluation suite: '{$suite}'. Available suites: " . implode(', ', array_keys(self::SUITES)));
        }

        $filename = self::SUITES[$normalizedSuite];
        $filepath = $this->dataDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filepath)) {
            throw new RuntimeException("Dataset file not found: {$filepath}");
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            throw new RuntimeException("Failed reading dataset file: {$filepath}");
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new RuntimeException("Invalid JSON structure in dataset file: {$filepath}");
        }

        return $data;
    }

    /**
     * Load multiple suites by comma-separated string or array.
     *
     * @param array<int, string>|string $suites
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function loadSuites(array|string $suites): array
    {
        if (is_string($suites)) {
            $suiteList = array_map('trim', explode(',', $suites));
        } else {
            $suiteList = $suites;
        }

        if (in_array('all', $suiteList, true)) {
            $suiteList = array_keys(self::SUITES);
        }

        $loaded = [];
        foreach ($suiteList as $suite) {
            if ($suite === '') {
                continue;
            }
            $loaded[$suite] = $this->loadSuite($suite);
        }

        return $loaded;
    }

    /**
     * Returns list of supported suites.
     *
     * @return array<int, string>
     */
    public static function getSupportedSuites(): array
    {
        return array_keys(self::SUITES);
    }
}
