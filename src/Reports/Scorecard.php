<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Reports;

use EidCloud\ArabicEvalBench\Metrics\ErrorTaxonomy;

/**
 * Generates structured CLI summaries, JSON reports, and Markdown scorecards.
 */
class Scorecard
{
    /**
     * @param string $model Model identifier or evaluated target name
     * @param array<string, array<string, mixed>> $suiteResults Results by suite
     * @param ErrorTaxonomy $taxonomy Error taxonomy
     * @param float $executionTime Seconds taken
     */
    public function __construct(
        private string $model,
        private array $suiteResults,
        private ErrorTaxonomy $taxonomy,
        private float $executionTime = 0.0
    ) {
    }

    /**
     * Build full structured report array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $totalTests = 0;
        $totalPassed = 0;
        $totalFailed = 0;
        $weightedAccuracySum = 0.0;
        $weightedF1Sum = 0.0;

        foreach ($this->suiteResults as $res) {
            $totalTests += $res['total'];
            $totalPassed += $res['passed'];
            $totalFailed += $res['failed'];
            $weightedAccuracySum += ($res['accuracy'] * $res['total']);
            $weightedF1Sum += ($res['f1'] * $res['total']);
        }

        $overallAccuracy = $totalTests > 0 ? round($weightedAccuracySum / $totalTests, 2) : 0.0;
        $overallF1 = $totalTests > 0 ? round($weightedF1Sum / $totalTests, 4) : 0.0;

        return [
            'benchmark' => 'eidcloud-arabic-eval-bench',
            'version' => '1.0.0',
            'model' => $this->model,
            'timestamp' => date('c'),
            'execution_time_seconds' => round($this->executionTime, 3),
            'summary' => [
                'total_tests' => $totalTests,
                'passed' => $totalPassed,
                'failed' => $totalFailed,
                'pass_rate' => $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 2) : 0.0,
                'overall_accuracy' => $overallAccuracy,
                'overall_f1' => $overallF1,
            ],
            'suites' => $this->suiteResults,
            'error_taxonomy' => $this->taxonomy->getDistribution(),
        ];
    }

    /**
     * Return JSON string format.
     */
    public function toJson(bool $pretty = true): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        return (string)json_encode($this->toArray(), $flags);
    }

    /**
     * Format terminal ANSI scorecard.
     */
    public function toCliOutput(): string
    {
        $report = $this->toArray();
        $summary = $report['summary'];

        $out = "\n";
        $out .= "\033[1;36m=========================================================================\033[0m\n";
        $out .= "\033[1;32m      📊 EidCloud Arabic AI Benchmark & Evaluation Suite v1.0.0          \033[0m\n";
        $out .= "\033[1;36m=========================================================================\033[0m\n";
        $out .= " Model Target   : \033[1;33m{$this->model}\033[0m\n";
        $out .= " Execution Time : {$report['execution_time_seconds']}s\n";
        $out .= " Total Tests    : {$summary['total_tests']} | Passed: \033[1;32m{$summary['passed']}\033[0m | Failed: \033[1;31m{$summary['failed']}\033[0m\n";
        $out .= " Pass Rate      : \033[1;32m{$summary['pass_rate']}%\033[0m\n";
        $out .= " Overall Acc    : \033[1;32m{$summary['overall_accuracy']}%\033[0m | F1 Score: \033[1;32m{$summary['overall_f1']}\033[0m\n";
        $out .= "\033[1;36m-------------------------------------------------------------------------\033[0m\n";
        $out .= sprintf(" %-40s | %-6s | %-8s | %-6s\n", "Capability Suite", "Tests", "Accuracy", "F1");
        $out .= "\033[1;36m-------------------------------------------------------------------------\033[0m\n";

        foreach ($report['suites'] as $s) {
            $statusColor = ($s['accuracy'] >= 80.0) ? "\033[1;32m" : (($s['accuracy'] >= 60.0) ? "\033[1;33m" : "\033[1;31m");
            $out .= sprintf(
                " %-40s | %-6d | %s%6.2f%%\033[0m  | %-6.4f\n",
                $s['title'],
                $s['total'],
                $statusColor,
                $s['accuracy'],
                $s['f1']
            );
        }

        $out .= "\033[1;36m-------------------------------------------------------------------------\033[0m\n";
        $out .= "\033[1;35m📌 Error Taxonomy Breakdown:\033[0m\n";

        $hasErrors = false;
        foreach ($report['error_taxonomy'] as $code => $err) {
            if ($err['count'] > 0) {
                $hasErrors = true;
                $out .= sprintf("   - %-42s : %3d errors (%.1f%%)\n", $err['name'], $err['count'], $err['percentage']);
            }
        }
        if (!$hasErrors) {
            $out .= "   \033[1;32m✓ Zero errors detected across evaluated benchmark cases!\033[0m\n";
        }

        $out .= "\033[1;36m=========================================================================\033[0m\n\n";

        return $out;
    }

    /**
     * Generate GitHub Flavored Markdown scorecard table.
     */
    public function toMarkdown(): string
    {
        $report = $this->toArray();
        $summary = $report['summary'];

        $md = "# 📊 Arabic AI Evaluation Scorecard: `{$this->model}`\n\n";
        $md .= "| Metric | Value |\n";
        $md .= "| :--- | :--- |\n";
        $md .= "| **Benchmark** | EidCloud Arabic AI Eval Bench v1.0.0 |\n";
        $md .= "| **Timestamp** | `{$report['timestamp']}` |\n";
        $md .= "| **Overall Accuracy** | **{$summary['overall_accuracy']}%** |\n";
        $md .= "| **Macro F1 Score** | **{$summary['overall_f1']}** |\n";
        $md .= "| **Pass Rate** | {$summary['pass_rate']}% ({$summary['passed']}/{$summary['total_tests']} tests) |\n";
        $md .= "| **Execution Time** | {$report['execution_time_seconds']}s |\n\n";

        $md .= "### 📋 Capability Breakdown\n\n";
        $md .= "| Capability Suite | Total Tests | Pass Rate | Accuracy | F1 Score |\n";
        $md .= "| :--- | :---: | :---: | :---: | :---: |\n";

        foreach ($report['suites'] as $s) {
            $passRate = $s['total'] > 0 ? round(($s['passed'] / $s['total']) * 100, 1) : 0.0;
            $md .= sprintf("| **%s** | %d | %0.1f%% | %0.2f%% | %0.4f |\n", $s['title'], $s['total'], $passRate, $s['accuracy'], $s['f1']);
        }

        $md .= "\n### 🔍 Error Taxonomy\n\n";
        $md .= "| Error Category | Count | Percentage |\n";
        $md .= "| :--- | :---: | :---: |\n";
        foreach ($report['error_taxonomy'] as $err) {
            $md .= sprintf("| %s | %d | %0.2f%% |\n", $err['name'], $err['count'], $err['percentage']);
        }

        return $md;
    }
}
