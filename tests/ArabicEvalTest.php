<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Tests;

use EidCloud\ArabicEvalBench\EvalEngine;
use EidCloud\ArabicEvalBench\Datasets\DatasetLoader;
use EidCloud\ArabicEvalBench\Metrics\AccuracyF1Scorer;
use EidCloud\ArabicEvalBench\Metrics\ArabicNormalizer;
use EidCloud\ArabicEvalBench\Metrics\DerWerScorer;
use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;

/**
 * Unit and integration tests for EidCloud Arabic AI Eval Bench.
 */
class ArabicEvalTest
{
    private int $passes = 0;
    private int $failures = 0;

    public function runAll(): bool
    {
        $methods = get_class_methods($this);
        echo "\n\033[1;36mRunning ArabicEvalBench Test Suite...\033[0m\n\n";

        foreach ($methods as $method) {
            if (str_starts_with($method, 'test')) {
                try {
                    $this->$method();
                    echo "  \033[1;32m✓\033[0m {$method}\n";
                    $this->passes++;
                } catch (\Throwable $e) {
                    echo "  \033[1;31m✗\033[0m {$method}: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")\n";
                    $this->failures++;
                }
            }
        }

        echo "\n\033[1;36m-------------------------------------------------------------------------\033[0m\n";
        echo "Tests: " . ($this->passes + $this->failures) . " | Passed: \033[1;32m{$this->passes}\033[0m | Failed: \033[1;31m{$this->failures}\033[0m\n";
        echo "\033[1;36m-------------------------------------------------------------------------\033[0m\n\n";

        return $this->failures === 0;
    }

    private function assertTrue(bool $condition, string $msg = 'Expected condition to be true'): void
    {
        if (!$condition) {
            throw new \RuntimeException($msg);
        }
    }

    private function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($msg ?: ("Expected " . var_export($expected, true) . ", got " . var_export($actual, true)));
        }
    }

    public function testArabicNormalizer(): void
    {
        $raw = "أحمد  وإبراهيم  فى المدرسةِ";
        $normalized = ArabicNormalizer::normalize($raw);
        $this->assertEquals("احمد وابراهيم في المدرسه", $normalized);

        $arabizi = "Ezayak Ya 7abibi";
        $normArabizi = ArabicNormalizer::normalizeArabizi($arabizi);
        $this->assertEquals("ezayak ya 7abibi", $normArabizi);
    }

    public function testDerWerScorerExactMatch(): void
    {
        $scorer = new DerWerScorer();
        $truth = "كَتَبَ الطَّالِبُ الدَّرْسَ";
        $pred = "كَتَبَ الطَّالِبُ الدَّرْسَ";

        $res = $scorer->score($pred, $truth);
        $this->assertEquals(0.0, $res['der']);
        $this->assertEquals(0.0, $res['wer']);
        $this->assertEquals(100.0, $res['der_accuracy']);
        $this->assertEquals(100.0, $res['wer_accuracy']);
    }

    public function testDerWerScorerWithMistakes(): void
    {
        $scorer = new DerWerScorer();
        $truth = "كَتَبَ الطَّالِبُ";
        $pred = "كُتِبَ الطَّالِبِ"; // 2 Tashkeel errors (Damma instead of Fatha, Kasra instead of Damma)

        $res = $scorer->score($pred, $truth);
        $this->assertTrue($res['der'] > 0);
        $this->assertTrue($res['wer'] > 0);
        $this->assertTrue($res['char_errors'] >= 2);
    }

    public function testAccuracyF1Scorer(): void
    {
        $scorer = new AccuracyF1Scorer();
        $res = $scorer->computeF1("الطلاب يكتبون الدرس", "الطلاب يكتبون الدرس");
        $this->assertEquals(1.0, $res['f1']);

        $em = $scorer->computeExactMatch("أحمد في المدرسة", "احمد فى المدرسه");
        $this->assertEquals(100.0, $em);
    }

    public function testJsonF1Scorer(): void
    {
        $scorer = new AccuracyF1Scorer();
        $truth = ["name" => "أحمد", "age" => 25, "active" => true];
        $pred = ["name" => "احمد", "age" => 25, "active" => true];

        $res = $scorer->computeJsonF1($pred, $truth);
        $this->assertTrue($res['exact_match']);
        $this->assertEquals(100.0, $res['field_accuracy']);
        $this->assertEquals(1.0, $res['f1']);
    }

    public function testDatasetLoader(): void
    {
        $loader = new DatasetLoader();
        $suites = DatasetLoader::getSupportedSuites();
        $this->assertEquals(6, count($suites));

        foreach ($suites as $s) {
            $data = $loader->loadSuite($s);
            $this->assertTrue(count($data) > 0, "Suite {$s} should not be empty");
        }
    }

    public function testErrorTaxonomy(): void
    {
        $taxonomy = new ErrorTaxonomy();
        $taxonomy->record('SYNTAX_ERROR', 'test_1', 'Bad JSON');
        $taxonomy->record('HARAKAT_ERROR', 'test_2', 'Wrong Fatha');

        $this->assertEquals(2, $taxonomy->getTotal());
        $dist = $taxonomy->getDistribution();
        $this->assertEquals(1, $dist['SYNTAX_ERROR']['count']);
        $this->assertEquals(50.0, $dist['SYNTAX_ERROR']['percentage']);
    }

    public function testEvalEngineEndToEnd(): void
    {
        $engine = new EvalEngine();
        $predictions = $engine->generateGroundTruthPredictions('all');
        $scorecard = $engine->evaluate($predictions, 'all', 'unit-test-model');

        $report = $scorecard->toArray();
        $this->assertEquals('unit-test-model', $report['model']);
        $this->assertEquals(100.0, $report['summary']['overall_accuracy']);
        $this->assertEquals(1.0, $report['summary']['overall_f1']);
        $this->assertEquals(20, $report['summary']['total_tests']);
        $this->assertEquals(20, $report['summary']['passed']);
        $this->assertEquals(0, $report['summary']['failed']);

        // Check report formats
        $cli = $scorecard->toCliOutput();
        $this->assertTrue(str_contains($cli, 'EidCloud Arabic AI Benchmark'));

        $json = $scorecard->toJson();
        $this->assertTrue(str_contains($json, '"overall_accuracy": 100'));

        $md = $scorecard->toMarkdown();
        $this->assertTrue(str_contains($md, 'Capability Breakdown'));
    }
}
