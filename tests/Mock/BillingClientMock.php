<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{

    private array $dataUsers = [
        'admin' => [
            'username' => 'admin@mail.com',
            'password' => '123456',
            'roles' => [
                'ROLE_USER',
                'ROLE_ADMIN',
            ],
            'balance' => 100,
            'access_token' => '',
            'refresh_token' => 'admin',
        ],
        'user' => [
            'username' => 'test@mail.ru',
            'password' => '123456',
            'roles' => [
                'ROlE_USER',
            ],
            'balance' => 200,
            'access_token' => '',
            'refresh_token' => 'user',
        ],
    ];


    public function __construct(
        private string $billingingUrl,
        private bool $ex = false,
    ) {
        $this->dataUsers['admin']['token'] = $_ENV['ADMIN_TOKEN'];
        $this->dataUsers['user']['token'] = $_ENV['USER_TOKEN'];
    }

    public function auth(array $data, bool $ex = false): array
    {
        if ($this->ex) {
            throw new BillingUnavailableException();
        }

        foreach ($this->dataUsers as $dataUser) {
            if ($data['username'] === $dataUser['username'] && $data['password'] === $dataUser['password']) {
                return [
                    'token' => $dataUser['token'],
                    'refresh_token' => $dataUser['refresh_token'],
                ];
            }
        }
        return ['code' => 401, 'message' => 'Invalid credentials.'];
    }

    public function register(array $data, bool $ex = false): array
    {
        if ($this->ex) {
            throw new BillingUnavailableException();
        }

        foreach ($this->dataUsers as $dataUser) {
            if ($dataUser['username'] === $data['username']) {
                return ['code' => 400, 'message' => 'Пользователь с таким email уже существует'];
            }
        }

        $newUser = [
            'username' => $data['username'],
            'password' => $data['password'],
            'roles' => [
                'ROLE_USER',
            ],
            'balance' => 0,
            'token' => 'new_user_token',
            'refresh_token' => explode('@', $data['username'])[0],
        ];
        $newUser['token'] = $_ENV['NEW_USER_TOKEN'];
        $this->dataUsers['new_user'] = $newUser;

        return [
            'token' => $newUser['token'],
            'roles' => $newUser['roles'],
            'refresh_token' => $newUser['refresh_token'],
        ];

    }

    public function userCurrent(string $token, bool $ex = false): array
    {
        if ($this->ex) {
            throw new BillingUnavailableException();
        }

        $user = null;

        foreach ($this->dataUsers as $dataUser) {
            if($dataUser['token'] === $token) {
                $user = $dataUser;
            }
        }

        if ($user) {
            unset($user['token'], $user['password'], $user['refresh_token']);
            return $user;
        }

        return ['code' => 401, 'message' => 'Invalid JWT Token'];
    }

//    public function refreshToken(string $refreshToken): array
//    {
//        return [
//            'token' => $this->dataUsers[$refreshToken]['token'],
//        ];
//    }



}