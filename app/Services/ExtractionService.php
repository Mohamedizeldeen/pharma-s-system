<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ExtractionService
{
    /**
     * Extract medicine name from text using NLP and pattern matching
     */
    public function extractMedicineName(string $text): ?string
    {
        // Normalize text
        $normalizedText = $this->normalizeText($text);

        Log::info('Extracting medicine name from normalized text', ['text' => $normalizedText]);

        // Try different extraction methods
        $medicineName = $this->extractByPatterns($normalizedText)
            ?? $this->extractByCommonPhrases($normalizedText)
            ?? $this->extractFirstSignificantWord($normalizedText);

        return $medicineName;
    }

    /**
     * Normalize Arabic and English text
     */
    private function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');

        // Remove Arabic diacritics (tashkeel)
        $text = preg_replace('/[\x{064B}-\x{065F}]/u', '', $text);

        // Normalize Arabic letters
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $text = str_replace('ة', 'ه', $text);
        $text = str_replace('ى', 'ي', $text);

        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Extract medicine name using common patterns
     */
    private function extractByPatterns(string $text): ?string
    {
        // Pattern 1: "هل يتوفر [medicine]" or "هل عندكم [medicine]"
        if (preg_match('/(هل يتوفر|هل عندكم|ابحث عن|اريد|ابي)\s+(.+?)(\s|$|\?)/u', $text, $matches)) {
            return trim($matches[2]);
        }

        // Pattern 2: "medicine متوفر؟" or "في medicine؟"
        if (preg_match('/\s+([a-zA-Z\x{0600}-\x{06FF}]+)\s+(متوفر|موجود)/u', $text, $matches)) {
            return trim($matches[1]);
        }

        // Pattern 3: Numbers and units (e.g., "paracetamol 500mg")
        if (preg_match('/([a-zA-Z\x{0600}-\x{06FF}]+)\s*\d+\s*(mg|ml|g|%)?/u', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract using common question phrases
     */
    private function extractByCommonPhrases(string $text): ?string
    {
        $phrases = [
            'هل يتوفر',
            'هل عندكم',
            'ابحث عن',
            'اريد',
            'ابي',
            'في عندكم',
            'موجود',
            'متوفر',
            'احتاج',
            'اين اجد',
            'where can i find',
            'do you have',
            'is there',
            'i need',
            'looking for',
        ];

        foreach ($phrases as $phrase) {
            $phrase = $this->normalizeText($phrase);
            if (str_contains($text, $phrase)) {
                // Remove the phrase and get what comes after
                $parts = explode($phrase, $text, 2);
                if (isset($parts[1])) {
                    $remaining = trim($parts[1]);
                    // Get first word/phrase after the question phrase
                    if (preg_match('/^([a-zA-Z\x{0600}-\x{06FF}0-9\s]+?)(\s|$|\?|؟)/u', $remaining, $matches)) {
                        return trim($matches[1]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extract first significant word (fallback)
     */
    private function extractFirstSignificantWord(string $text): ?string
    {
        // Remove common stop words
        $stopWords = [
            'في', 'من', 'الى', 'على', 'عن', 'هل', 'ما', 'لا', 'نعم',
            'the', 'a', 'an', 'is', 'are', 'do', 'does', 'have', 'has'
        ];

        $words = explode(' ', $text);
        
        foreach ($words as $word) {
            $word = trim($word);
            $normalized = $this->normalizeText($word);
            
            // Skip stop words and very short words
            if (strlen($word) < 3 || in_array($normalized, $stopWords)) {
                continue;
            }

            // Skip pure numbers
            if (is_numeric($word)) {
                continue;
            }

            // Return first significant word
            return $word;
        }

        return null;
    }

    /**
     * Clean extracted medicine name
     */
    public function cleanMedicineName(string $name): string
    {
        // Remove special characters except spaces and hyphens
        $name = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $name);
        
        // Remove extra spaces
        $name = preg_replace('/\s+/', ' ', $name);
        
        return trim($name);
    }
}
