<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\JwtService;
use CodeIgniter\HTTP\ResponseInterface;

class ProtectedController extends BaseController
{
    public function index(): ResponseInterface
    {
        $token = preg_replace('/^Bearer\s+/i', '', $this->request->getHeaderLine('Authorization'));
        $user = (new JwtService())->decode((string) $token) ?? [];

        return $this->response->setJSON([
            'message' => 'Geschuetzter Bereich erreichbar.',
            'data' => [
                'user' => $user['sub'] ?? null,
                'name' => $user['name'] ?? null,
            ],
        ]);
    }
}
