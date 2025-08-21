<?php

namespace App\Services;

use App\Models\ImportProfile;
use App\Models\ImportRecord;
use Illuminate\Support\Facades\DB;

class DuplicateDetectionService
{
    /**
     * Detect duplicates for a source record using profile configuration
     */
    public function detectDuplicates(array $sourceData, ImportProfile $profile): array
    {
        $duplicateConfig = $profile->getDefaultDuplicateDetection();
        $matchingStrategy = $profile->getDefaultMatchingStrategy();
        
        if (!$duplicateConfig['enabled']) {
            return [
                'is_duplicate' => false,
                'matches' => [],
                'confidence' => 0,
            ];
        }

        $matches = [];
        $maxConfidence = 0;

        // Primary field matching (highest priority)
        $primaryMatches = $this->findByPrimaryFields(
            $sourceData, 
            $matchingStrategy['primary_fields'],
            $profile,
            $duplicateConfig['case_sensitive']
        );

        foreach ($primaryMatches as $match) {
            $confidence = $this->calculateMatchConfidence($sourceData, $match, $matchingStrategy);
            $matches[] = [
                'record' => $match,
                'confidence' => $confidence,
                'matching_fields' => $this->getMatchingFields($sourceData, $match, $matchingStrategy['primary_fields']),
                'match_type' => 'primary'
            ];
            $maxConfidence = max($maxConfidence, $confidence);
        }

        // Secondary field matching if no strong primary matches
        if ($maxConfidence < 0.9 && !empty($matchingStrategy['secondary_fields'])) {
            $secondaryMatches = $this->findBySecondaryFields(
                $sourceData,
                $matchingStrategy['secondary_fields'],
                $profile,
                $duplicateConfig['case_sensitive']
            );

            foreach ($secondaryMatches as $match) {
                // Skip if already found in primary matches
                if (collect($matches)->contains(fn($m) => $m['record']['id'] === $match['id'])) {
                    continue;
                }

                $confidence = $this->calculateMatchConfidence($sourceData, $match, $matchingStrategy);
                $matches[] = [
                    'record' => $match,
                    'confidence' => $confidence,
                    'matching_fields' => $this->getMatchingFields($sourceData, $match, $matchingStrategy['secondary_fields']),
                    'match_type' => 'secondary'
                ];
                $maxConfidence = max($maxConfidence, $confidence);
            }
        }

        // Fuzzy matching if enabled and no strong matches
        if ($matchingStrategy['fuzzy_matching'] && $maxConfidence < $matchingStrategy['similarity_threshold']) {
            $fuzzyMatches = $this->findByFuzzyMatching(
                $sourceData,
                array_merge($matchingStrategy['primary_fields'], $matchingStrategy['secondary_fields']),
                $profile,
                $matchingStrategy['similarity_threshold']
            );

            foreach ($fuzzyMatches as $match) {
                // Skip if already found
                if (collect($matches)->contains(fn($m) => $m['record']['id'] === $match['id'])) {
                    continue;
                }

                $confidence = $this->calculateFuzzyConfidence($sourceData, $match, $matchingStrategy);
                $matches[] = [
                    'record' => $match,
                    'confidence' => $confidence,
                    'matching_fields' => $this->getFuzzyMatchingFields($sourceData, $match),
                    'match_type' => 'fuzzy'
                ];
                $maxConfidence = max($maxConfidence, $confidence);
            }
        }

        // Sort matches by confidence (highest first)
        usort($matches, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return [
            'is_duplicate' => $maxConfidence >= $matchingStrategy['similarity_threshold'],
            'matches' => array_slice($matches, 0, 10), // Return top 10 matches
            'confidence' => $maxConfidence,
            'detection_strategy' => $duplicateConfig,
            'matching_strategy' => $matchingStrategy,
        ];
    }

    /**
     * Find matches by primary fields (exact matching)
     */
    private function findByPrimaryFields(array $sourceData, array $primaryFields, ImportProfile $profile, bool $caseSensitive): array
    {
        $matches = [];

        foreach ($primaryFields as $field) {
            if (!isset($sourceData[$field]) || empty($sourceData[$field])) {
                continue;
            }

            $value = $sourceData[$field];
            
            // Query existing import records for this profile
            $query = ImportRecord::where('import_profile_id', $profile->id)
                ->where('import_action', '!=', 'failed')
                ->whereJsonContains('source_data->' . $field, $value);

            if (!$caseSensitive && is_string($value)) {
                // For case-insensitive matching, we need to use raw SQL
                $query->whereRaw('LOWER(source_data->>?) = LOWER(?)', [$field, $value]);
            }

            $fieldMatches = $query->get();
            
            foreach ($fieldMatches as $match) {
                $matches[] = $match->toArray();
            }
        }

        return array_unique($matches, SORT_REGULAR);
    }

    /**
     * Find matches by secondary fields
     */
    private function findBySecondaryFields(array $sourceData, array $secondaryFields, ImportProfile $profile, bool $caseSensitive): array
    {
        return $this->findByPrimaryFields($sourceData, $secondaryFields, $profile, $caseSensitive);
    }

    /**
     * Find matches using fuzzy matching algorithms
     */
    private function findByFuzzyMatching(array $sourceData, array $fields, ImportProfile $profile, float $threshold): array
    {
        $matches = [];

        // Get all records for comparison (limited to reasonable batch size)
        $allRecords = ImportRecord::where('import_profile_id', $profile->id)
            ->where('import_action', '!=', 'failed')
            ->limit(1000) // Prevent memory issues
            ->get();

        foreach ($allRecords as $record) {
            $similarity = $this->calculateFuzzySimilarity($sourceData, $record->source_data, $fields);
            
            if ($similarity >= $threshold) {
                $recordArray = $record->toArray();
                $recordArray['fuzzy_similarity'] = $similarity;
                $matches[] = $recordArray;
            }
        }

        // Sort by similarity
        usort($matches, fn($a, $b) => $b['fuzzy_similarity'] <=> $a['fuzzy_similarity']);

        return $matches;
    }

    /**
     * Calculate fuzzy similarity between two records
     */
    private function calculateFuzzySimilarity(array $source, array $target, array $fields): float
    {
        $totalWeight = count($fields);
        $matchScore = 0;

        foreach ($fields as $field) {
            $sourceValue = $source[$field] ?? '';
            $targetValue = $target[$field] ?? '';

            if (empty($sourceValue) && empty($targetValue)) {
                $matchScore += 1;
                continue;
            }

            if (empty($sourceValue) || empty($targetValue)) {
                continue; // Skip empty values
            }

            // String similarity using Levenshtein distance
            if (is_string($sourceValue) && is_string($targetValue)) {
                $similarity = $this->stringSimilarity($sourceValue, $targetValue);
                $matchScore += $similarity;
            } elseif ($sourceValue === $targetValue) {
                $matchScore += 1;
            }
        }

        return $totalWeight > 0 ? $matchScore / $totalWeight : 0;
    }

    /**
     * Calculate string similarity using Levenshtein distance
     */
    private function stringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
    }

    /**
     * Calculate overall match confidence
     */
    private function calculateMatchConfidence(array $sourceData, array $matchRecord, array $strategy): float
    {
        $matchingFields = array_merge($strategy['primary_fields'], $strategy['secondary_fields']);
        $totalFields = count($matchingFields);
        $matchingScore = 0;

        foreach ($matchingFields as $field) {
            $sourceValue = $sourceData[$field] ?? null;
            $targetValue = $matchRecord['source_data'][$field] ?? null;

            if ($sourceValue === $targetValue) {
                $matchingScore += 1;
            } elseif (is_string($sourceValue) && is_string($targetValue)) {
                $similarity = $this->stringSimilarity($sourceValue, $targetValue);
                $matchingScore += $similarity;
            }
        }

        return $totalFields > 0 ? $matchingScore / $totalFields : 0;
    }

    /**
     * Calculate fuzzy match confidence
     */
    private function calculateFuzzyConfidence(array $sourceData, array $matchRecord, array $strategy): float
    {
        return $matchRecord['fuzzy_similarity'] ?? 0;
    }

    /**
     * Get list of fields that matched
     */
    private function getMatchingFields(array $sourceData, array $matchRecord, array $fields): array
    {
        $matchingFields = [];

        foreach ($fields as $field) {
            $sourceValue = $sourceData[$field] ?? null;
            $targetValue = $matchRecord['source_data'][$field] ?? null;

            if ($sourceValue === $targetValue) {
                $matchingFields[] = $field;
            }
        }

        return $matchingFields;
    }

    /**
     * Get fuzzy matching fields with similarity scores
     */
    private function getFuzzyMatchingFields(array $sourceData, array $matchRecord): array
    {
        $matchingFields = [];
        $targetData = $matchRecord['source_data'] ?? [];

        foreach ($sourceData as $field => $sourceValue) {
            $targetValue = $targetData[$field] ?? null;
            
            if ($sourceValue && $targetValue && is_string($sourceValue) && is_string($targetValue)) {
                $similarity = $this->stringSimilarity($sourceValue, $targetValue);
                if ($similarity > 0.7) { // Only include reasonably similar fields
                    $matchingFields[] = [
                        'field' => $field,
                        'similarity' => $similarity,
                        'source_value' => $sourceValue,
                        'target_value' => $targetValue,
                    ];
                }
            }
        }

        return $matchingFields;
    }

    /**
     * Generate hash for duplicate detection
     */
    public function generateDuplicateHash(array $sourceData, array $fields): string
    {
        $hashData = [];
        
        foreach ($fields as $field) {
            if (isset($sourceData[$field])) {
                $hashData[$field] = is_string($sourceData[$field]) 
                    ? strtolower(trim($sourceData[$field])) 
                    : $sourceData[$field];
            }
        }

        ksort($hashData);
        return hash('sha256', serialize($hashData));
    }

    /**
     * Check if import should proceed based on profile configuration and duplicate detection
     */
    public function shouldProceedWithImport(array $duplicateResult, ImportProfile $profile): array
    {
        $isDuplicate = $duplicateResult['is_duplicate'];
        $importMode = $profile->import_mode ?? 'upsert';
        
        if (!$isDuplicate) {
            return [
                'proceed' => true,
                'action' => 'create',
                'reason' => 'No duplicates found'
            ];
        }

        // Get the best match
        $bestMatch = $duplicateResult['matches'][0] ?? null;
        
        switch ($importMode) {
            case 'create':
                if ($profile->shouldSkipDuplicates()) {
                    return [
                        'proceed' => false,
                        'action' => 'skip',
                        'reason' => 'Duplicate found and profile configured to skip duplicates',
                        'duplicate_record' => $bestMatch
                    ];
                } else {
                    return [
                        'proceed' => true,
                        'action' => 'create',
                        'reason' => 'Duplicate found but profile allows creating duplicates',
                        'duplicate_record' => $bestMatch
                    ];
                }

            case 'update':
                if ($bestMatch) {
                    return [
                        'proceed' => true,
                        'action' => 'update',
                        'reason' => 'Duplicate found, updating existing record',
                        'duplicate_record' => $bestMatch
                    ];
                } else {
                    return [
                        'proceed' => false,
                        'action' => 'skip',
                        'reason' => 'Update mode but no existing record found'
                    ];
                }

            case 'upsert':
            default:
                if ($bestMatch) {
                    return [
                        'proceed' => true,
                        'action' => $profile->shouldUpdateDuplicates() ? 'update' : 'skip',
                        'reason' => $profile->shouldUpdateDuplicates() 
                            ? 'Duplicate found, updating existing record' 
                            : 'Duplicate found, skipping update',
                        'duplicate_record' => $bestMatch
                    ];
                } else {
                    return [
                        'proceed' => true,
                        'action' => 'create',
                        'reason' => 'No duplicates found, creating new record'
                    ];
                }
        }
    }
}