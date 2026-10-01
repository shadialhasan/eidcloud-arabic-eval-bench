<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Metrics;

/**
 * Normalizes Arabic text across orthographic variants, Alef shapes,
 * Taa Marbuta / Haa, Ya / Alef Maksura, and whitespace.
 */
class ArabicNormalizer
{
    /**
     * Standard normalization for benchmark matching.
     */
    public static function normalize(string $text, bool $stripDiacritics = true): string
    {
        if ($stripDiacritics) {
            $text = DerWerScorer::stripDiacritics($text);
        }

        // Tatweel / Kashida removal
        $text = preg_replace('/[\x{0640}]/u', '', $text) ?? $text;

        // Normalize Alefs (أ, إ, آ, ٱ -> ا)
        $text = preg_replace('/[\x{0622}\x{0623}\x{0625}\x{0671}]/u', 'ا', $text) ?? $text;

        // Normalize Taa Marbuta (ة -> ه)
        $text = preg_replace('/[\x{0629}]/u', 'ه', $text) ?? $text;

        // Normalize Ya / Alef Maksura (ى -> ي)
        $text = preg_replace('/[\x{0649}]/u', 'ي', $text) ?? $text;

        // Normalize Persian / Urdu Arabic characters if any (e.g., ک -> ك, ی -> ي)
        $text = str_replace(['ک', 'ی', 'گ', 'چ', 'پ', 'ژ'], ['ك', 'ي', 'ك', 'ج', 'ب', 'ز'], $text);

        // Normalize whitespace and trim
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Normalize Arabizi (Franco-Arabic) text to lowercased canonical form.
     */
    public static function normalizeArabizi(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return $text;
    }
}
