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

    public function __construct(
        private string $billingingUrl,
        private bool $ex = false,
    ) {
    }

    public function setThrowException(bool $flag): void
    {
        $this->shouldThrowException = $flag;
    }

    public function auth(array $data, bool $ex = false): array
    {

        dump('AUTH called', $credentials);
        die(); // остановит выполнение теста

        if ($this->ex) {
            throw new BillingUnavailableException();
        }

        if ($data['email'] === 'admin@mail.com' && $data['password'] === '123456') {
            return $this->dataUsers[0];
        }


        return ['code' => 401, 'message' => 'Invalid credentials.'];
    }
}
