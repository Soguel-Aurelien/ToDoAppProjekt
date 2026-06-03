<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\JwtService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Api;

class AuthController extends BaseController
{
    public function login(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->failJson('Ungueltige JSON-Daten.');
        }

        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $users = config(Api::class)->jwtUsers;

        if ($username === '' || $password === '') {
            return $this->failJson('Benutzername und Passwort sind erforderlich.', 422, [
                'username' => 'Benutzername ist erforderlich.',
                'password' => 'Passwort ist erforderlich.',
            ]);
        }

        if (! isset($users[$username]) || ! hash_equals($users[$username]['password'], $password)) {
            return $this->failJson('Ungueltige Login-Daten.', 401);
        }

        $token = (new JwtService())->encode([
            'sub' => $username,
            'name' => $users[$username]['name'] ?? $username,
        ]);

        return $this->response->setJSON([
            'message' => 'Login erfolgreich.',
            'token_type' => 'Bearer',
            'expires_in' => config(Api::class)->jwtTtl,
            'access_token' => $token,
        ]);
    }

    private function failJson(string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON([
                'message' => $message,
                'errors' => $errors,
            ]);
    }
}
