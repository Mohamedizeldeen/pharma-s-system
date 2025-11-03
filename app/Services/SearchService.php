<?php

namespace App\Services;

use App\Models\medicines;
use App\Models\branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchService
{
    /**
     * Search for medicine in database with fuzzy matching
     */
    public function searchMedicine(string $medicineName, ?array $userLocation = null): Collection
    {
        $normalizedName = $this->normalizeName($medicineName);

        Log::info('Searching for medicine', [
            'original' => $medicineName,
            'normalized' => $normalizedName
        ]);

        // Try exact match first
        $results = $this->exactSearch($normalizedName);

        // If no exact match, try fuzzy search
        if ($results->isEmpty()) {
            $results = $this->fuzzySearch($normalizedName);
        }

        // If still no results, try partial match
        if ($results->isEmpty()) {
            $results = $this->partialSearch($normalizedName);
        }

        return $results;
    }

    /**
     * Normalize medicine name for search
     */
    private function normalizeName(string $name): string
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = preg_replace('/[\x{064B}-\x{065F}]/u', '', $name);
        $name = str_replace(['أ', 'إ', 'آ'], 'ا', $name);
        $name = str_replace('ة', 'ه', $name);
        $name = str_replace('ى', 'ي', $name);
        $name = trim($name);
        
        return $name;
    }

    /**
     * Exact name search
     */
    private function exactSearch(string $name): Collection
    {
        return medicines::query()
            ->select([
                'medicines.id as medicine_id',
                'medicines.name as medicine_name',
                'medicines.scientific_name',
                'medicines.price',
                'medicines.quantity',
                'medicines.image',
                'branches.id as branch_id',
                'branches.name as branch_name',
                'branches.address',
                'branches.phone',
                'branches.latitude',
                'branches.longitude',
                'branches.opening_hours',
                'branches.closing_hours',
                'pharmas.id as pharma_id',
                'pharmas.name as pharmacy_name',
            ])
            ->join('branches', 'medicines.branch_id', '=', 'branches.id')
            ->join('pharmas', 'medicines.pharma_id', '=', 'pharmas.id')
            ->where('medicines.quantity', '>', 0)
            ->where(function ($query) use ($name) {
                $query->whereRaw('LOWER(medicines.name) = ?', [$name])
                    ->orWhereRaw('LOWER(medicines.scientific_name) = ?', [$name]);
            })
            ->orderBy('medicines.quantity', 'desc')
            ->get();
    }

    /**
     * Fuzzy search using LIKE and SOUNDEX
     */
    private function fuzzySearch(string $name): Collection
    {
        return medicines::query()
            ->select([
                'medicines.id as medicine_id',
                'medicines.name as medicine_name',
                'medicines.scientific_name',
                'medicines.price',
                'medicines.quantity',
                'medicines.image',
                'branches.id as branch_id',
                'branches.name as branch_name',
                'branches.address',
                'branches.phone',
                'branches.latitude',
                'branches.longitude',
                'branches.opening_hours',
                'branches.closing_hours',
                'pharmas.id as pharma_id',
                'pharmas.name as pharmacy_name',
            ])
            ->join('branches', 'medicines.branch_id', '=', 'branches.id')
            ->join('pharmas', 'medicines.pharma_id', '=', 'pharmas.id')
            ->where('medicines.quantity', '>', 0)
            ->where(function ($query) use ($name) {
                // LIKE search with wildcards
                $query->where('medicines.name', 'LIKE', "%{$name}%")
                    ->orWhere('medicines.scientific_name', 'LIKE', "%{$name}%");
            })
            ->orderBy('medicines.quantity', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Partial search (word by word)
     */
    private function partialSearch(string $name): Collection
    {
        $words = explode(' ', $name);
        $words = array_filter($words, fn($w) => strlen($w) >= 3);

        if (empty($words)) {
            return collect();
        }

        return medicines::query()
            ->select([
                'medicines.id as medicine_id',
                'medicines.name as medicine_name',
                'medicines.scientific_name',
                'medicines.price',
                'medicines.quantity',
                'medicines.image',
                'branches.id as branch_id',
                'branches.name as branch_name',
                'branches.address',
                'branches.phone',
                'branches.latitude',
                'branches.longitude',
                'branches.opening_hours',
                'branches.closing_hours',
                'pharmas.id as pharma_id',
                'pharmas.name as pharmacy_name',
            ])
            ->join('branches', 'medicines.branch_id', '=', 'branches.id')
            ->join('pharmas', 'medicines.pharma_id', '=', 'pharmas.id')
            ->where('medicines.quantity', '>', 0)
            ->where(function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->orWhere('medicines.name', 'LIKE', "%{$word}%")
                        ->orWhere('medicines.scientific_name', 'LIKE', "%{$word}%");
                }
            })
            ->orderBy('medicines.quantity', 'desc')
            ->limit(15)
            ->get();
    }

    /**
     * Calculate similarity score using Levenshtein distance
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = $this->normalizeName($str1);
        $str2 = $this->normalizeName($str2);

        $maxLength = max(strlen($str1), strlen($str2));
        
        if ($maxLength === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        $similarity = 1 - ($distance / $maxLength);

        return $similarity;
    }
}
