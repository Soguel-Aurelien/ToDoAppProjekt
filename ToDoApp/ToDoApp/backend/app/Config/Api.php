<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Api extends BaseConfig
{
    public array $keys = [
        'dev-frontend-key-2026' => 'frontend-dev',
        'dev-admin-key-2026' => 'admin-dev',
    ];

    public string $jwtSecret = 'change-this-secret-in-production-2026';

    public int $jwtTtl = 3600;

    public array $jwtUsers = [
        'admin' => [
            'password' => 'Admin123!',
            'name' => 'API Admin',
        ],
    ];
}
