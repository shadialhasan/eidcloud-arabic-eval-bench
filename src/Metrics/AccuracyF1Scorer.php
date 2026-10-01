<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Metrics;

/**
 * Computes Exact Accuracy, Token-level Precision, Recall, and F1 Score
 * for Arabic text, tokens, and multi-label benchmark predictions.
 */
class AccuracyF1Scorer
{
    /**
     * Compute Exact Match accuracy (0.0 - 100.0).
     */
    public function computeExactMatch(string $predicted, string $groundTruth, bool $normalize = true): float
    {
        if ($normalize) {
            $predicted = ArabicNormalizer::normalize($predicted);
            $groundTruth = ArabicNormalizer::normalize($groundTruth);
        }

        return ($predicted === $groundTruth) ? 100.0 : 0.0;
    }

    /**
     * Compute token-level Precision, Recall, and F1 Score.
     *
     * @return array{precision: float, recall: float, f1: float}
     */
    public function computeF1(string $predicted, string $groundTruth, bool $normalize = true): array
    {
        if ($normalize) {
            $predicted = ArabicNormalizer::normalize($predicted);
            $groundTruth = ArabicNormalizer::normalize($groundTruth);
        }

        $predTokens = array_values(array_filter(explode(' ', $predicted)));
        $truthTokens = array_values(array_filter(explode(' ', $groundTruth)));

        if (empty($predTokens) && empty($truthTokens)) {
            return ['precision' => 1.0, 'recall' => 1.0, 'f1' => 1.0];
        }

        if (empty($predTokens) || empty($truthTokens)) {
            return ['precision' => 0.0, 'recall' => 0.0, 'f1' => 0.0];
        }

        $predCounts = array_count_values($predTokens);
        $truthCounts = array_count_values($truthTokens);

        $commonCount = 0;
        foreach ($predCounts as $token => $count) {
            if (isset($truthCounts[$token])) {
                $commonCount += min($count, $truthCounts[$token]);
            }
        }

        if ($commonCount === 0) {
            return ['precision' => 0.0, 'recall' => 0.0, 'f1' => 0.0];
        }

        $precision = $commonCount / count($predTokens);
        $recall = $commonCount / count($truthTokens);
        $f1 = (2 * $precision * $recall) / ($precision + $recall);

        return [
            'precision' => round($precision, 4),
            'recall' => round($recall, 4),
            'f1' => round($f1, 4),
        ];
    }

    /**
     * Compare JSON structures deeply and calculate structural F1 and field accuracy.
     *
     * @param mixed $predicted
     * @param mixed $groundTruth
     * @return array{
     *   exact_match: bool,
     *   field_accuracy: float,
     *   total_fields: int,
     *   matched_fields: int,
     *   f1: float
     * }
     */
    public function computeJsonF1(mixed $predicted, mixed $groundTruth): array
    {
        $flattenPred = $this->flattenArray($predicted);
        $flattenTruth = $this->flattenArray($groundTruth);

        $totalFields = count($flattenTruth);
        if ($totalFields === 0) {
            $isMatch = empty($flattenPred);
            return [
                'exact_match' => $isMatch,
                'field_accuracy' => $isMatch ? 100.0 : 0.0,
                'total_fields' => 0,
                'matched_fields' => 0,
                'f1' => $isMatch ? 1.0 : 0.0,
            ];
        }

        $matchedFields = 0;
        foreach ($flattenTruth as $key => $truthVal) {
            if (array_key_exists($key, $flattenPred)) {
                $predVal = $flattenPred[$key];
                if ($this->valuesMatch($predVal, $truthVal)) {
                    $matchedFields++;
                }
            }
        }

        $fieldAcc = ($matchedFields / $totalFields) * 100.0;

        $precision = count($flattenPred) > 0 ? ($matchedFields / count($flattenPred)) : 0.0;
        $recall = $totalFields > 0 ? ($matchedFields / $totalFields) : 0.0;
        $f1 = ($precision + $recall) > 0 ? (2 * $precision * $recall) / ($precision + $recall) : 0.0;

        $exactMatch = ($matchedFields === $totalFields) && (count($flattenPred) === $totalFields);

        return [
            'exact_match' => $exactMatch,
            'field_accuracy' => round($fieldAcc, 2),
            'total_fields' => $totalFields,
            'matched_fields' => $matchedFields,
            'f1' => round($f1, 4),
        ];
    }

    private function valuesMatch(mixed $a, mixed $b): bool
    {
        if (is_string($a) && is_string($b)) {
            return ArabicNormalizer::normalize($a) === ArabicNormalizer::normalize($b);
        }
        if (is_numeric($a) && is_numeric($b)) {
            return (float)$a === (float)$b;
        }
        return $a === $b;
    }

    /**
     * Flatten nested array into dot notation keys.
     *
     * @return array<string, mixed>
     */
    private function flattenArray(mixed $array, string $prefix = ''): array
    {
        if (!is_array($array)) {
            return [$prefix => $array];
        }

        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string)$key : $prefix . '.' . $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
