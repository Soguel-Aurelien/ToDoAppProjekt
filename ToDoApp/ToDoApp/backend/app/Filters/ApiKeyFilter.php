<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Api;

class ApiKeyFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return null;
        }

        $apiKey = $request->getHeaderLine('X-API-Key') ?: $request->getGet('key');
        $config = config(Api::class);

        if (! is_string($apiKey) || ! array_key_exists($apiKey, $config->keys)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'message' => 'Ungueltiger oder fehlender API Key.',
                    'errors' => [
                        'key' => 'Senden Sie einen gueltigen API Key per X-API-Key Header oder ?key= URL-Parameter.',
                    ],
                ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
