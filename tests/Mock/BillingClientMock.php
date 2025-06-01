<?php

namespace App\Tests\Mock;

use App\Exception\InvalidCredentialsException;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class BillingClientMock extends BillingClient
{
    private $user = [
        'email' => 'user@mail.ru',
        'password' => '123456',
        'roles' => ['ROLE_USER'],
        'balance' => 1259.99,
    ];
    private $admin = [
        'email' => 'admin@mail.ru',
        'password' => '123456',
        'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        'balance' => 99999.99,
    ];

    private $newUser = [
        'email' => 'new_user@mail.ru',
        'password' => 'password',
        'roles' => ['ROLE_USER'],
        'balance' => 0.0
    ];

    private function generateToken(string $username, array $roles): string
    {
        $signingKey = "signingKey";

        $header = base64_encode(json_encode([
            "alg" => "HS512",
            "typ" => "JWT"
        ]));

        $payload = base64_encode(json_encode([
            'username' => $username,
            'roles' => $roles,
            'exp' => (new \DateTime('+1 day'))->getTimestamp(),
        ]));

        $signature = base64_encode(hash_hmac('sha512', "$header.$payload", $signingKey, true));

        return "$header.$payload.$signature";
    }

    /**
     * @throws InvalidCredentialsException
     */
    public function auth(string $username, string $password): array
    {
        $account = null;

        if ($username === $this->user['email']) {
            $account = $this->user;
        } elseif ($username === $this->admin['email']) {
            $account = $this->admin;
        } elseif ($username === $this->newUser['email']) {
            $account = $this->newUser;
        }

        if (!$account) {
            throw new InvalidCredentialsException('User with given username not found');
        }

        if ($password !== $account['password']) {
            throw new InvalidCredentialsException('Invalid password');
        }

        $token = $this->generateToken($account['email'], $account['roles']);

        return [
            'user' => $account['email'],
            'access_token' => $token,
            'refresh_token' => $token, // Заглушка
        ];
    }
    public function register(string $username, string $password): array
    {
        // Проверяем, существует ли уже пользователь с таким email
        if ($username === $this->user['email'] ||
            $username === $this->admin['email'] ||
            $username === $this->newUser['email']) {
            throw new InvalidCredentialsException('User with this email already exists');
        }

        // Генерируем токены для нового пользователя
        $token = $this->generateToken($username, ['ROLE_USER']);

        // Создаем запись о новом пользователе
        $this->newUser = [
            'email' => $username,
            'password' => $password,
            'roles' => ['ROLE_USER'],
            'balance' => 0.0
        ];

        return [
            'access_token' => $token,
            'refresh_token' => $token, // Для упрощения используем тот же токен
            'user' => [
                'email' => $username,
                'roles' => ['ROLE_USER'],
            ],
        ];
    }

    public function getCurrentUser(string $token): User
    {
        $jwtParts = explode('.', $token);
        $payload = json_decode(base64_decode($jwtParts[1]), JSON_OBJECT_AS_ARRAY);
        $user = new User();

        if ($payload['username'] === $this->user['email']) {
            $user->setEmail($this->user['email'])
                ->setBalance($this->user['balance'])
                ->setRoles($this->user['roles'])
                ->setApiToken($token);
        } elseif ($payload['username'] === $this->admin['email']) {
            $user->setEmail($this->admin['email'])
                ->setBalance($this->admin['balance'])
                ->setRoles($this->admin['roles'])
                ->setApiToken($token);
        } elseif ($payload['username'] === $this->newUser['email']) {
            $user->setEmail($this->newUser['email'])
                ->setBalance($this->newUser['balance'])
                ->setRoles($this->newUser['roles'])
                ->setApiToken($token);
        } else {
            throw new AuthenticationException('Invalid JWT token');
        }

        return $user;
    }

    public function refreshToken(User $user): User
    {
        return $user;
    }

    public function coursesList(): array
    {
        return [
            [
                "code" => "python-junior",
                "type" => 'rent',
                "price" => 299.99
            ],
            [
                "code" => "introduction-to-neural-networks",
                "type" => 'rent',
                "price" => 500.00
            ],
            [
                "code" => "industrial-web-development",
                "type" => 'pay',
                "price" => 850.00
            ],
            [
                "code" => "basics-of-computer-vision",
                "type" => 'pay',
                "price" => 350.99
            ],
            [
                "code" => "ros2-course",
                "type" => 'free',
                "price" => 0.00
            ],
        ];
    }

    public function courseInfoByCode(string $course_code): array
    {
        if ($course_code == "python-junior") {
            return [
                "code" => "python-junior",
                "type" => 'rent',
                "price" => 299.99
            ];
        } elseif ($course_code == "introduction-to-neural-networks") {
            return [
                "code" => "introduction-to-neural-networks",
                "type" => 'rent',
                "price" => 500.00
            ];
        } elseif ($course_code == "industrial-web-development") {
            return [
                "code" => "industrial-web-development",
                "type" => 'pay',
                "price" => 850.00
            ];
        } elseif ($course_code == "basics-of-computer-vision") {
            return [
                "code" => "basics-of-computer-vision",
                "type" => 'pay',
                "price" => 350.99
            ];
        } elseif ($course_code == "ros2-course") {
            return [
                "code" => "ros2-course",
                "type" => 'free',
                "price" => 0.00
            ];
        } else {
            return [
                "code" => "ros2-course",
                "type" => 'free',
                "price" => 0.00
            ];
        }
    }

    public function isCourseAvailable(string $token, string $course_code): bool|string
    {
        if ($course_code == "python-junior") {
            return '30.12.2025';
        } elseif ($course_code == "introduction-to-neural-networks") {
            return '30.12.2025';
        } elseif ($course_code == "industrial-web-development") {
            return true;
        } elseif ($course_code == "basics-of-computer-vision") {
            return true;
        } elseif ($course_code == "ros2-course") {
            return true;
        } else {
            return true;
        }
    }

    public function payCourse(string $token, string $course_code): bool
    {
        return true;
    }
}