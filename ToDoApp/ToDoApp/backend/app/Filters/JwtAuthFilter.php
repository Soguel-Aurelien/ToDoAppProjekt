<?php

namespace App\Filters;

use App\Libraries\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return null;
        }

        $header = $request->getHeaderLine('Authorization');
        if (! preg_match('/^Bearer\s+(.+)$/', $header, $matches)) {
            return $this->unauthorized('JWT fehlt im Authorization Header.');
        }

        $claims = (new JwtService())->decode($matches[1]);
        if ($claims === null) {
            return $this->unauthorized('JWT ist ungueltig oder abgelaufen.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function unauthorized(string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON([
                'message' => $message,
                'errors' => [
                    'authorization' => $message,
                ],
            ]);
    }
}
