<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Api;
use Config\Database;

class ApiLogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! str_starts_with(trim($request->getUri()->getPath(), '/'), 'api/')) {
            return null;
        }

        $user = $this->resolveApiUser($request);
        $row = [
            'api_user' => $user,
            'method' => strtoupper($request->getMethod()),
            'path' => '/' . trim($request->getUri()->getPath(), '/'),
            'query_string' => $request->getUri()->getQuery(),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        log_message('info', 'API request {method} {path}?{query} -> {status} user={user}', [
            'method' => $row['method'],
            'path' => $row['path'],
            'query' => $row['query_string'],
            'status' => $row['status_code'],
            'user' => $row['api_user'] ?? 'unknown',
        ]);

        try {
            $db = Database::connect();
            if ($db->tableExists('api_request_logs')) {
                $db->table('api_request_logs')->insert($row);
            }
        } catch (\Throwable $exception) {
            log_message('error', 'API database logging failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    private function resolveApiUser(RequestInterface $request): ?string
    {
        $apiKey = $request->getHeaderLine('X-API-Key') ?: $request->getGet('key');
        $keys = config(Api::class)->keys;

        return is_string($apiKey) && isset($keys[$apiKey]) ? $keys[$apiKey] : null;
    }
}
