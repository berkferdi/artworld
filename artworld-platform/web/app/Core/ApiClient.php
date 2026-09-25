<?php

declare(strict_types=1);

namespace Web\Core;

use Throwable;

final class ApiClient
{
    private string $baseUrl;
    private int $timeout;
    private int $retry;
    private int $cacheTtl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) Config::get('API_BASE_URL', ''), '/');
        $this->timeout = Config::getInt('API_TIMEOUT', 12);
        $this->retry = max(0, Config::getInt('API_RETRY', 1));
        $this->cacheTtl = Config::getInt('API_CACHE_TTL', 60);
    }

    /**
     * @param array<string, scalar|null> $query
     * @return array{success:bool, message?:string, data?:mixed, meta?:mixed}|null
     */
    public function get(string $path, array $query = [], bool $useCache = true): ?array
    {
        $url = $this->buildUrl($path, $query);
        $cacheKey = 'api:' . $url;

        if ($useCache && $this->cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $attempts = 1 + $this->retry;
        $lastError = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $response = $this->request($url);
                if ($response === null) {
                    $lastError = 'empty response';
                    continue;
                }
                if (!isset($response['success'])) {
                    $this->log('invalid json envelope: ' . $url);
                    return null;
                }
                if ($useCache && $this->cacheTtl > 0 && ($response['success'] ?? false) === true) {
                    Cache::put($cacheKey, $response, $this->cacheTtl);
                }
                return $response;
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                $this->log('request failed: ' . $url . ' — ' . $lastError);
                usleep(150000);
            }
        }

        $this->log('giving up: ' . $url . ' — ' . ($lastError ?? 'unknown'));
        return null;
    }

    /** @return mixed */
    public function data(string $path, array $query = [], mixed $default = null): mixed
    {
        $response = $this->get($path, $query);
        if ($response === null || ($response['success'] ?? false) !== true) {
            return $default;
        }
        return $response['data'] ?? $default;
    }

    /**
     * @param array<string, scalar|null> $query
     */
    private function buildUrl(string $path, array $query): string
    {
        $path = '/' . ltrim($path, '/');
        $url = $this->baseUrl . $path;
        $filtered = [];
        foreach ($query as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $filtered[$key] = $value;
        }
        if ($filtered !== []) {
            $url .= '?' . http_build_query($filtered);
        }
        return $url;
    }

    /** @return array<string, mixed>|null */
    private function request(string $url): ?array
    {
        if (function_exists('curl_init')) {
            return $this->requestCurl($url);
        }
        return $this->requestStream($url);
    }

    /** @return array<string, mixed>|null */
    private function requestCurl(string $url): ?array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: ArtWorldWeb/1.0',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException($error !== '' ? $error : 'curl_exec failed');
        }
        if ($status >= 400) {
            throw new \RuntimeException('HTTP ' . $status);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('JSON decode failed');
        }
        return $decoded;
    }

    /** @return array<string, mixed>|null */
    private function requestStream(string $url): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => "Accept: application/json\r\nUser-Agent: ArtWorldWeb/1.0\r\n",
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            throw new \RuntimeException('stream request failed');
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('JSON decode failed');
        }
        return $decoded;
    }

    private function log(string $message): void
    {
        $dir = WEB_STORAGE . '/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = '[' . date('c') . '] ' . $message . PHP_EOL;
        @file_put_contents(WEB_STORAGE . '/api-client.log', $line, FILE_APPEND | LOCK_EX);
    }
}
