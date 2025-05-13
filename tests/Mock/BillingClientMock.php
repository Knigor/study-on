<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{
    private bool $shouldThrowException = false;

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

    public function setThrowException(bool $flag): void
    {
        $this->shouldThrowException = $flag;
    }

    public function auth(array $credentials): array
    {
        if ($this->shouldThrowException) {
            throw new BillingUnavailableException();
        }

        foreach ($this->dataUsers as $user) {
            if (
                $credentials['email'] === $user['email'] &&
                $credentials['password'] === $user['password']
            ) {
                return [
                    'access_token' => $user['access_token'],
                    'refresh_token' => $user['refresh_token'],
                    'user' => [
                        'email' => $user['email'],
                        'roles' => $user['roles'],
                        'balance' => $user['balance'],
                    ]
                ];
            }
        }

        return [
            'message' => 'Invalid credentials',
        ];
    }
}
