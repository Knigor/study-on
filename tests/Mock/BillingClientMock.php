<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{

    private array $dataUsers = [
        [
            'email' => 'admin@mail.com',
            'password' => '123456',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
            'balance' => 100,
            'access_token' => 'mock_admin_token',
            'refresh_token' => 'mock_admin_refresh',
        ],
        [
            'email' => 'test@mail.ru',
            'password' => '123456',
            'roles' => ['ROLE_USER'],
            'balance' => 200,
            'access_token' => 'mock_user_token',
            'refresh_token' => 'mock_user_refresh',
        ],
    ];


    public function __construct(
        private readonly bool $ex = false,
    ) {

    }

    public function register(array $data, $ex = false): array
    {
        if ($this->ex) {
            throw new BillingUnavailableException();
        }
        
        
        if ($data['email'] === 'admin@mail.ru') {
            return [
                "error" => "Email already taken",
                "message" => "User with this email already exists"
            ];
        }

        return [
            'access_token' => 'someAccessToken',
            'refresh_token' => 'someRefreshToken',
            'user' => [
                'email' => 'new@example.com',
                'roles' => ['ROLE_USER'],
            ],
        ];
    }

    public function auth(array $data, $ex = false): array
    {
        if ($this->ex) {
            throw new BillingUnavailableException();
        }

        if ($data['email'] === 'admin@mail.ru' && $data['password'] === '123456') {
            return [
                'access_token' => 'someAccessToken',
                'refresh_token' => 'someRefreshToken',
                'user' => [
                    'email' => 'admin@mail.ru',
                    "roles" => [
                        "ROLE_ADMIN",
                        "ROLE_USER"
                    ]
                ],
            ];
        }

        return ['code' => 403, 'message' => 'Invalid credentials.'];
    }
}
