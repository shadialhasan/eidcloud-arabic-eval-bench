<?php

declare(strict_types=1);

namespace EidCloud\ArabicEvalBench\Metrics;

/**
 * Calculates Diacritization Error Rate (DER) and Word Error Rate (WER)
 * for Arabic Tashkeel evaluation with character and token level precision.
 */
class DerWerScorer
{
    /** Arabic Tashkeel Unicode Range (Fathatan to Sukun, plus Shadda and Superscript Alef) */
    public const HARAKAT = [
        "\u{064B}", // Fathatan
        "\u{064C}", // Dammatan
        "\u{064D}", // Kasratan
        "\u{064E}", // Fatha
        "\u{064F}", // Damma
        "\u{0650}", // Kasra
        "\u{0651}", // Shadda
        "\u{0652}", // Sukun
        "\u{0670}", // Superscript Alef
    ];

    /**
     * Compute DER (Diacritization Error Rate) and WER (Word Error Rate).
     *
     * @param string $predicted Predicted text with diacritics
     * @param string $groundTruth Reference text with ground truth diacritics
     * @param bool $ignoreLastChar Whether to ignore syntactic case ending (I'rab)
     * @return array{
     *   der: float,
     *   wer: float,
     *   total_chars: int,
     *   char_errors: int,
     *   total_words: int,
     *   word_errors: int,
     *   der_accuracy: float,
     *   wer_accuracy: float
     * }
     */
    public function score(string $predicted, string $groundTruth, bool $ignoreLastChar = false): array
    {
        $predTokens = $this->tokenize($predicted);
        $truthTokens = $this->tokenize($groundTruth);

        $totalWords = count($truthTokens);
        if ($totalWords === 0) {
            return [
                'der' => 0.0,
                'wer' => 0.0,
                'total_chars' => 0,
                'char_errors' => 0,
                'total_words' => 0,
                'word_errors' => 0,
                'der_accuracy' => 100.0,
                'wer_accuracy' => 100.0,
            ];
        }

        $wordErrors = 0;
        $totalChars = 0;
        $charErrors = 0;

        $maxTokens = max(count($predTokens), count($truthTokens));

        for ($i = 0; $i < $maxTokens; $i++) {
            $pWord = $predTokens[$i] ?? '';
            $tWord = $truthTokens[$i] ?? '';

            if ($pWord === '' && $tWord !== '') {
                $wordErrors++;
                $tChars = $this->extractBaseWithDiacritics($tWord);
                $totalChars += count($tChars);
                $charErrors += count($tChars);
                continue;
            }

            if ($tWord === '' && $pWord !== '') {
                $wordErrors++;
                $pChars = $this->extractBaseWithDiacritics($pWord);
                $totalChars += count($pChars);
                $charErrors += count($pChars);
                continue;
            }

            $pParsed = $this->extractBaseWithDiacritics($pWord);
            $tParsed = $this->extractBaseWithDiacritics($tWord);

            $wordMismatch = false;
            $len = max(count($pParsed), count($tParsed));
            $lastIndex = count($tParsed) - 1;

            for ($j = 0; $j < $len; $j++) {
                if ($ignoreLastChar && $j === $lastIndex) {
                    continue; // Skip grammatical case ending if requested
                }

                $tChar = $tParsed[$j] ?? null;
                $pChar = $pParsed[$j] ?? null;

                if ($tChar !== null) {
                    $totalChars++;
                }

                if ($tChar === null || $pChar === null) {
                    $charErrors++;
                    $wordMismatch = true;
                } elseif ($tChar['base'] !== $pChar['base'] || $tChar['diacritics'] !== $pChar['diacritics']) {
                    $charErrors++;
                    $wordMismatch = true;
                }
            }

            if ($wordMismatch) {
                $wordErrors++;
            }
        }

        $der = $totalChars > 0 ? ($charErrors / $totalChars) : 0.0;
        $wer = $totalWords > 0 ? ($wordErrors / $totalWords) : 0.0;

        return [
            'der' => round($der, 4),
            'wer' => round($wer, 4),
            'total_chars' => $totalChars,
            'char_errors' => $charErrors,
            'total_words' => $totalWords,
            'word_errors' => $wordErrors,
            'der_accuracy' => round(max(0.0, (1.0 - $der) * 100), 2),
            'wer_accuracy' => round(max(0.0, (1.0 - $wer) * 100), 2),
        ];
    }

    /**
     * Splits string into whitespace-separated word tokens.
     *
     * @return array<int, string>
     */
    public function tokenize(string $text): array
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($normalized === '') {
            return [];
        }
        return explode(' ', $normalized);
    }

    /**
     * Extracts base characters with their attached diacritics.
     *
     * @return array<int, array{base: string, diacritics: string}>
     */
    public function extractBaseWithDiacritics(string $word): array
    {
        $chars = mb_str_split($word);
        $result = [];
        $currentBase = null;
        $currentDiacritics = [];

        foreach ($chars as $ch) {
            if ($this->isDiacritic($ch)) {
                if ($currentBase !== null) {
                    $currentDiacritics[] = $ch;
                }
            } else {
                if ($currentBase !== null) {
                    sort($currentDiacritics);
                    $result[] = [
                        'base' => $currentBase,
                        'diacritics' => implode('', $currentDiacritics),
                    ];
                }
                $currentBase = $ch;
                $currentDiacritics = [];
            }
        }

        if ($currentBase !== null) {
            sort($currentDiacritics);
            $result[] = [
                'base' => $currentBase,
                'diacritics' => implode('', $currentDiacritics),
            ];
        }

        return $result;
    }

    /**
     * Checks if character is an Arabic Tashkeel/haraka mark.
     */
    public function isDiacritic(string $char): bool
    {
        return in_array($char, self::HARAKAT, true);
    }

    /**
     * Strips all Tashkeel / Harakat from Arabic string.
     */
    public static function stripDiacritics(string $text): string
    {
        return str_replace(self::HARAKAT, '', $text);
    }
}
