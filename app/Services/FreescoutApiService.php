<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreescoutApiService
{
    /**
     * Test connection to FreeScout API.
     */
    public function testConnection(array $config): array
    {
        try {
            $instanceUrl = rtrim($config['instance_url'], '/');
            $apiKey = $config['api_key'];

            Log::info('Testing FreeScout connection', ['url' => $instanceUrl]);

            // Test basic API connectivity with a simple endpoint
            $response = Http::withHeaders([
                'X-FreeScout-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get("{$instanceUrl}/api/mailboxes");

            Log::info('Connection test response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_size' => strlen($response->body()),
            ]);

            if ($response->successful()) {
                $mailboxes = $response->json();
                
                // Parse mailboxes count correctly
                $mailboxesCount = 0;
                if (isset($mailboxes['_embedded']['mailboxes'])) {
                    $mailboxesCount = count($mailboxes['_embedded']['mailboxes']);
                } elseif (is_array($mailboxes)) {
                    $mailboxesCount = count($mailboxes);
                } elseif (isset($mailboxes['data'])) {
                    $mailboxesCount = count($mailboxes['data']);
                }
                
                // Get additional stats for the test
                $stats = $this->getDataStatistics($config);

                // Detect version from response body or headers
                $apiVersion = $this->detectApiVersion($response->headers());
                if (!$apiVersion && isset($mailboxes['_links'])) {
                    // If we see HAL links, it's likely a newer version
                    $apiVersion = 'v1 (HAL)';
                } elseif (!$apiVersion) {
                    $apiVersion = 'v1';
                }

                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'response_time' => $response->handlerStats()['total_time'] ?? 0,
                    'api_version' => $apiVersion,
                    'mailboxes_count' => $mailboxesCount,
                    'stats' => $stats,
                    'tested_at' => now()->toISOString(),
                ];
            } else {
                $errorBody = $response->body();
                $errorMessage = "HTTP {$response->status()}";
                
                try {
                    $errorJson = $response->json();
                    if (isset($errorJson['message'])) {
                        $errorMessage .= ': ' . $errorJson['message'];
                    } elseif (isset($errorJson['error'])) {
                        $errorMessage .= ': ' . $errorJson['error'];
                    }
                } catch (\Exception $e) {
                    $errorMessage .= ': ' . substr($errorBody, 0, 100);
                }

                return [
                    'success' => false,
                    'message' => 'API connection failed',
                    'error' => $errorMessage,
                    'status_code' => $response->status(),
                    'tested_at' => now()->toISOString(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('FreeScout API connection test failed', [
                'instance_url' => $config['instance_url'] ?? 'not provided',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage(),
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get data statistics from FreeScout API.
     */
    public function getDataStatistics(array $config): array
    {
        try {
            $instanceUrl = rtrim($config['instance_url'], '/');
            $apiKey = $config['api_key'];

            $headers = [
                'X-FreeScout-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ];

            Log::info('Getting FreeScout statistics', ['url' => $instanceUrl]);

            // Get mailboxes count
            $mailboxesResponse = Http::withHeaders($headers)
                ->timeout(30)
                ->get("{$instanceUrl}/api/mailboxes");
            
            $mailboxesCount = 0;
            $mailboxesData = [];
            if ($mailboxesResponse->successful()) {
                $mailboxes = $mailboxesResponse->json();
                Log::info('Mailboxes response', ['response' => $mailboxes]);
                
                if (isset($mailboxes['_embedded']['mailboxes'])) {
                    // FreeScout uses HAL format with _embedded
                    $mailboxesData = $mailboxes['_embedded']['mailboxes'];
                    $mailboxesCount = count($mailboxesData);
                } elseif (is_array($mailboxes)) {
                    $mailboxesData = $mailboxes;
                    $mailboxesCount = count($mailboxes);
                } elseif (isset($mailboxes['data'])) {
                    $mailboxesData = $mailboxes['data'];
                    $mailboxesCount = count($mailboxes['data']);
                }
            } else {
                Log::error('Mailboxes API failed', [
                    'status' => $mailboxesResponse->status(),
                    'body' => $mailboxesResponse->body()
                ]);
            }

            // Get conversations count
            $conversationsCount = 0;
            if ($mailboxesCount > 0) {
                $conversationsResponse = Http::withHeaders($headers)
                    ->timeout(30)
                    ->get("{$instanceUrl}/api/conversations", [
                        'per_page' => 1, // Just need count, not actual data
                        'page' => 1,
                    ]);

                if ($conversationsResponse->successful()) {
                    $conversations = $conversationsResponse->json();
                    Log::info('Conversations response for count', ['response' => $conversations]);
                    
                    if (isset($conversations['page']['totalElements'])) {
                        // Spring Boot/HAL style pagination
                        $conversationsCount = $conversations['page']['totalElements'];
                    } elseif (isset($conversations['total'])) {
                        // Laravel style pagination
                        $conversationsCount = $conversations['total'];
                    } elseif (isset($conversations['meta']['total'])) {
                        // Alternative Laravel style
                        $conversationsCount = $conversations['meta']['total'];
                    } elseif (isset($conversations['_links']['last']['href'])) {
                        // Extract total from HAL pagination links
                        $conversationsCount = $this->extractTotalFromHalLinks($conversations['_links']);
                    } else {
                        // Fallback: try to get actual total by requesting a larger per_page
                        $fallbackResponse = Http::withHeaders($headers)
                            ->timeout(60)
                            ->get("{$instanceUrl}/api/conversations", [
                                'per_page' => 1000, // Get more data to estimate better
                                'page' => 1,
                            ]);
                        
                        if ($fallbackResponse->successful()) {
                            $fallbackData = $fallbackResponse->json();
                            if (isset($fallbackData['_embedded']['conversations'])) {
                                $conversationsCount = count($fallbackData['_embedded']['conversations']);
                                // If we got exactly 1000, there might be more
                                if ($conversationsCount === 1000) {
                                    Log::warning('Conversation count might be higher than 1000, using approximation');
                                }
                            } elseif (isset($fallbackData['data'])) {
                                $conversationsCount = count($fallbackData['data']);
                            }
                        }
                    }
                } else {
                    Log::error('Conversations API failed', [
                        'status' => $conversationsResponse->status(),
                        'body' => $conversationsResponse->body()
                    ]);
                }
            }

            // Get customers count
            $customersCount = 0;
            if ($mailboxesCount > 0) {
                $customersResponse = Http::withHeaders($headers)
                    ->timeout(30)
                    ->get("{$instanceUrl}/api/customers", [
                        'per_page' => 1, // Just need count
                        'page' => 1,
                    ]);

                if ($customersResponse->successful()) {
                    $customers = $customersResponse->json();
                    Log::info('Customers response for count', ['response' => $customers]);
                    
                    if (isset($customers['page']['totalElements'])) {
                        // Spring Boot/HAL style pagination
                        $customersCount = $customers['page']['totalElements'];
                    } elseif (isset($customers['total'])) {
                        // Laravel style pagination
                        $customersCount = $customers['total'];
                    } elseif (isset($customers['meta']['total'])) {
                        // Alternative Laravel style
                        $customersCount = $customers['meta']['total'];
                    } elseif (isset($customers['_links']['last']['href'])) {
                        // Extract total from HAL pagination links
                        $customersCount = $this->extractTotalFromHalLinks($customers['_links']);
                    } else {
                        // Fallback: try to get actual total by requesting a larger per_page
                        $fallbackResponse = Http::withHeaders($headers)
                            ->timeout(60)
                            ->get("{$instanceUrl}/api/customers", [
                                'per_page' => 1000, // Get more data to estimate better
                                'page' => 1,
                            ]);
                        
                        if ($fallbackResponse->successful()) {
                            $fallbackData = $fallbackResponse->json();
                            if (isset($fallbackData['_embedded']['customers'])) {
                                $customersCount = count($fallbackData['_embedded']['customers']);
                                // If we got exactly 1000, there might be more
                                if ($customersCount === 1000) {
                                    Log::warning('Customer count might be higher than 1000, using approximation');
                                }
                            } elseif (isset($fallbackData['data'])) {
                                $customersCount = count($fallbackData['data']);
                            }
                        }
                    }
                } else {
                    Log::error('Customers API failed', [
                        'status' => $customersResponse->status(),
                        'body' => $customersResponse->body()
                    ]);
                }
            }

            Log::info('Final statistics', [
                'conversations' => $conversationsCount,
                'customers' => $customersCount,
                'mailboxes' => $mailboxesCount,
            ]);

            return [
                'conversations' => $conversationsCount,
                'customers' => $customersCount,
                'mailboxes' => $mailboxesCount,
                'retrieved_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get FreeScout statistics', [
                'instance_url' => $config['instance_url'] ?? 'not provided',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'conversations' => 0,
                'customers' => 0,
                'mailboxes' => 0,
                'retrieved_at' => now()->toISOString(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract total count from HAL pagination links.
     */
    private function extractTotalFromHalLinks(array $links): int
    {
        if (isset($links['last']['href'])) {
            $lastLink = $links['last']['href'];
            // Parse URL to extract page parameter
            $urlParts = parse_url($lastLink);
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $queryParams);
                if (isset($queryParams['page']) && isset($queryParams['per_page'])) {
                    $lastPage = (int) $queryParams['page'];
                    $perPage = (int) $queryParams['per_page'];
                    // Rough estimate: (lastPage * perPage) gives us approximate total
                    return $lastPage * $perPage;
                }
            }
        }
        
        return 0;
    }

    /**
     * Detect API version from response headers.
     */
    private function detectApiVersion(array $headers): ?string
    {
        // Look for version information in headers
        foreach ($headers as $key => $value) {
            $key = strtolower($key);
            if (str_contains($key, 'version') || str_contains($key, 'api-version')) {
                return is_array($value) ? $value[0] : $value;
            }
        }

        // Default to unknown if not found
        return 'v1'; // FreeScout typically uses v1
    }

    /**
     * Get conversations from FreeScout API.
     */
    public function getConversations(array $config, array $options = []): array
    {
        try {
            $instanceUrl = rtrim($config['instance_url'], '/');
            $apiKey = $config['api_key'];

            $params = array_merge([
                'per_page' => $options['limit'] ?? 100,
                'page' => $options['page'] ?? 1,
            ], $options['filters'] ?? []);

            $response = Http::withHeaders([
                'X-FreeScout-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(60)->get("{$instanceUrl}/api/conversations", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: " . ($response->json()['message'] ?? $response->body()),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to get FreeScout conversations', [
                'instance_url' => $config['instance_url'] ?? 'not provided',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get customers from FreeScout API.
     */
    public function getCustomers(array $config, array $options = []): array
    {
        try {
            $instanceUrl = rtrim($config['instance_url'], '/');
            $apiKey = $config['api_key'];

            $params = array_merge([
                'per_page' => $options['limit'] ?? 100,
                'page' => $options['page'] ?? 1,
            ], $options['filters'] ?? []);

            $response = Http::withHeaders([
                'X-FreeScout-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(60)->get("{$instanceUrl}/api/customers", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: " . ($response->json()['message'] ?? $response->body()),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to get FreeScout customers', [
                'instance_url' => $config['instance_url'] ?? 'not provided',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get mailboxes from FreeScout API.
     */
    public function getMailboxes(array $config, array $options = []): array
    {
        try {
            $instanceUrl = rtrim($config['instance_url'], '/');
            $apiKey = $config['api_key'];

            $response = Http::withHeaders([
                'X-FreeScout-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get("{$instanceUrl}/api/mailboxes");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: " . ($response->json()['message'] ?? $response->body()),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to get FreeScout mailboxes', [
                'instance_url' => $config['instance_url'] ?? 'not provided',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get time entries from FreeScout API.
     */
    public function getTimeEntries(array $config, array $options = []): array
    {
        try {
            $instanceUrl = rtrim($config['instance_url'], '/');
            $apiKey = $config['api_key'];

            $params = array_merge([
                'per_page' => $options['limit'] ?? 100,
                'page' => $options['page'] ?? 1,
            ], $options['filters'] ?? []);

            // Note: FreeScout may not have a dedicated time entries endpoint
            // This might need to be retrieved from conversations or a specific module
            $response = Http::withHeaders([
                'X-FreeScout-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(60)->get("{$instanceUrl}/api/time-entries", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: " . ($response->json()['message'] ?? $response->body()),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to get FreeScout time entries', [
                'instance_url' => $config['instance_url'] ?? 'not provided',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get conversation threads from FreeScout API.
     */
    public function getConversationThreads(array $config, int $conversationId): array
    {
        try {
            $instanceUrl = rtrim($config['instance_url'], '/');
            $apiKey = $config['api_key'];

            $response = Http::withHeaders([
                'X-FreeScout-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get("{$instanceUrl}/api/conversations/{$conversationId}/threads");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: " . ($response->json()['message'] ?? $response->body()),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to get FreeScout conversation threads', [
                'instance_url' => $config['instance_url'] ?? 'not provided',
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get preview data for import configuration.
     */
    public function getPreviewData(array $config, array $options = []): array
    {
        try {
            $instanceUrl = rtrim($config['instance_url'], '/');
            $apiKey = $config['api_key'];

            $headers = [
                'X-FreeScout-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ];

            $sampleSize = $options['sample_size'] ?? 10;
            $previewData = [];

            // Get conversations with sample data
            $conversationsResult = $this->getConversations($config, [
                'limit' => $sampleSize,
                'page' => 1,
            ]);

            if ($conversationsResult['success']) {
                $conversationsData = $conversationsResult['data'];
                if (isset($conversationsData['_embedded']['conversations'])) {
                    // HAL format
                    $previewData['conversations'] = array_slice($conversationsData['_embedded']['conversations'], 0, $sampleSize);
                } elseif (isset($conversationsData['data'])) {
                    // Laravel pagination format
                    $previewData['conversations'] = array_slice($conversationsData['data'], 0, $sampleSize);
                } elseif (is_array($conversationsData)) {
                    // Simple array format
                    $previewData['conversations'] = array_slice($conversationsData, 0, $sampleSize);
                } else {
                    $previewData['conversations'] = [];
                }
            } else {
                $previewData['conversations'] = [];
            }

            // Get customers with sample data
            $customersResult = $this->getCustomers($config, [
                'limit' => $sampleSize,
                'page' => 1,
            ]);

            if ($customersResult['success']) {
                $customersData = $customersResult['data'];
                if (isset($customersData['_embedded']['customers'])) {
                    // HAL format
                    $previewData['customers'] = array_slice($customersData['_embedded']['customers'], 0, $sampleSize);
                } elseif (isset($customersData['data'])) {
                    // Laravel pagination format
                    $previewData['customers'] = array_slice($customersData['data'], 0, $sampleSize);
                } elseif (is_array($customersData)) {
                    // Simple array format
                    $previewData['customers'] = array_slice($customersData, 0, $sampleSize);
                } else {
                    $previewData['customers'] = [];
                }
            } else {
                $previewData['customers'] = [];
            }

            // Get mailboxes
            $mailboxesResult = $this->getMailboxes($config);
            if ($mailboxesResult['success']) {
                $mailboxesData = $mailboxesResult['data'];
                if (isset($mailboxesData['_embedded']['mailboxes'])) {
                    // HAL format
                    $previewData['mailboxes'] = $mailboxesData['_embedded']['mailboxes'];
                } elseif (isset($mailboxesData['data'])) {
                    // Laravel pagination format
                    $previewData['mailboxes'] = $mailboxesData['data'];
                } elseif (is_array($mailboxesData)) {
                    // Simple array format
                    $previewData['mailboxes'] = $mailboxesData;
                } else {
                    $previewData['mailboxes'] = [];
                }
            } else {
                $previewData['mailboxes'] = [];
            }

            // Get time entries with sample data
            $timeEntriesResult = $this->getTimeEntries($config, [
                'limit' => $sampleSize,
                'page' => 1,
            ]);

            if ($timeEntriesResult['success']) {
                $timeEntriesData = $timeEntriesResult['data'];
                if (isset($timeEntriesData['_embedded']['time_entries'])) {
                    // HAL format
                    $previewData['time_entries'] = array_slice($timeEntriesData['_embedded']['time_entries'], 0, $sampleSize);
                } elseif (isset($timeEntriesData['data'])) {
                    // Laravel pagination format
                    $previewData['time_entries'] = array_slice($timeEntriesData['data'], 0, $sampleSize);
                } elseif (is_array($timeEntriesData)) {
                    // Simple array format
                    $previewData['time_entries'] = array_slice($timeEntriesData, 0, $sampleSize);
                } else {
                    $previewData['time_entries'] = [];
                }
            } else {
                // Time entries might not be available in all FreeScout instances
                $previewData['time_entries'] = [];
            }

            return [
                'success' => true,
                'data' => $previewData,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get FreeScout preview data', [
                'instance_url' => $config['instance_url'] ?? 'not provided',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}