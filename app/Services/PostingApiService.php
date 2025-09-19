<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PostingApiService
{
    protected Client $client;
    protected string $baseUrl;
    protected string $apiKey;
    protected string $operatorId;
    protected int $timeout;
    protected bool $cacheEnabled;
    protected int $cacheTtl;
    protected string $cachePrefix;

    public function __construct()
    {
        $this->baseUrl = config('posting.api.base_url');
        $this->apiKey = config('posting.api.key');
        $this->operatorId = config('posting.api.operator_id');
        $this->timeout = config('posting.api.timeout');
        $this->cacheEnabled = config('posting.cache.enabled');
        $this->cacheTtl = config('posting.cache.ttl');
        $this->cachePrefix = config('posting.cache.prefix');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => [
                'x-api-key' => $this->apiKey,
                'x-operator-id' => $this->operatorId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Make a GET request to the API
     */
    public function get(string $endpoint, array $params = []): array
    {
        $cacheKey = $this->getCacheKey('GET', $endpoint, $params);

        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = $this->client->get($endpoint, [
                'query' => $params
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Handle null response (empty body or invalid JSON)
            if ($data === null) {
                $data = ['success' => true, 'status' => $response->getStatusCode()];
            }

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $data, $this->cacheTtl);
            }

            return $data;

        } catch (RequestException $e) {
            Log::error('API GET request failed', [
                'endpoint' => $endpoint,
                'params' => $params,
                'error' => $e->getMessage()
            ]);

            throw $this->handleException($e);
        }
    }

    /**
     * Make a POST request to the API
     */
    public function post(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $data
            ]);

            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);

            // Handle null response (empty body or invalid JSON)
            // For certain endpoints like /print, the response might be a plain text URL
            if ($responseData === null) {
                if (!empty($responseBody) && filter_var($responseBody, FILTER_VALIDATE_URL)) {
                    // If response is a valid URL (like S3 URL), return it as data
                    $responseData = ['url' => trim($responseBody), 'status' => $response->getStatusCode()];
                } else {
                    $responseData = ['success' => true, 'status' => $response->getStatusCode()];
                }
            }

            // Clear related cache entries
            $this->clearRelatedCache($endpoint);

            return $responseData;

        } catch (RequestException $e) {
            Log::error('API POST request failed', [
                'endpoint' => $endpoint,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            throw $this->handleException($e);
        }
    }

    /**
     * Make a PUT request to the API
     */
    public function put(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->client->put($endpoint, [
                'json' => $data
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            // Handle null response (empty body or invalid JSON)
            if ($responseData === null) {
                $responseData = ['success' => true, 'status' => $response->getStatusCode()];
            }

            // Clear related cache entries
            $this->clearRelatedCache($endpoint);

            return $responseData;

        } catch (RequestException $e) {
            Log::error('API PUT request failed', [
                'endpoint' => $endpoint,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            throw $this->handleException($e);
        }
    }

    /**
     * Make a DELETE request to the API
     */
    public function delete(string $endpoint): array
    {
        try {
            $response = $this->client->delete($endpoint);

            $responseBody = $response->getBody()->getContents();

            // Handle empty response body (common with DELETE requests)
            if (empty($responseBody)) {
                $responseData = ['success' => true, 'status' => $response->getStatusCode()];
            } else {
                $responseData = json_decode($responseBody, true);
                // If JSON decode fails, return success response
                if ($responseData === null) {
                    $responseData = ['success' => true, 'status' => $response->getStatusCode()];
                }
            }

            // Clear related cache entries
            $this->clearRelatedCache($endpoint);

            return $responseData;

        } catch (RequestException $e) {
            Log::error('API DELETE request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            throw $this->handleException($e);
        }
    }

    /**
     * Generate cache key for requests
     */
    protected function getCacheKey(string $method, string $endpoint, array $params = []): string
    {
        $key = $method . '_' . str_replace('/', '_', trim($endpoint, '/'));

        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }

        return $this->cachePrefix . $key;
    }

    /**
     * Clear cache entries related to an endpoint
     */
    protected function clearRelatedCache(string $endpoint): void
    {
        if (!$this->cacheEnabled) {
            return;
        }

        // Clear cache for the main resource (e.g., /drivers, /declarations)
        $resource = explode('/', trim($endpoint, '/'))[0];
        $pattern = $this->cachePrefix . 'GET_' . $resource . '*';

        // Note: This is a simplified cache clearing strategy
        // In production, you might want to implement a more sophisticated cache tagging system
        Cache::flush();
    }

    /**
     * Handle API exceptions and convert to application exceptions
     */
    protected function handleException(RequestException $e): \Exception
    {
        $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;

        switch ($statusCode) {
            case 401:
                return new \Exception('API authentication failed. Please check your API key.', 401);
            case 403:
                return new \Exception('API access forbidden. Insufficient permissions.', 403);
            case 404:
                return new \Exception('API resource not found.', 404);
            case 422:
                $response = $e->getResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : [];
                $message = $response['message'] ?? 'Validation failed';
                return new \Exception($message, 422);
            case 429:
                return new \Exception('API rate limit exceeded. Please try again later.', 429);
            case 500:
                return new \Exception('API server error. Please try again later.', 500);
            default:
                return new \Exception('API request failed: ' . $e->getMessage(), $statusCode);
        }
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->operatorId) && !empty($this->baseUrl);
    }

    /**
     * Test API connectivity
     */
    public function testConnection(): bool
    {
        try {
            // Try to make a simple GET request to test connectivity
            $this->get('/drivers', ['limit' => 1]);
            return true;
        } catch (\Exception $e) {
            Log::warning('API connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}