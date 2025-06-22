<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use App\Exception\CustomUserMessageAuthenticationException;
use App\Exception\InvalidCredentialsException;
use App\Exception\NotEnoughBalanceException;
use App\Security\User;
use DateTime;
use Exception;
use Symfony\Component\Security\Core\Exception\AuthenticationException;


class BillingClient
{
    public function __construct(
        private readonly string $billingUrl,
    ) {
    }

    private function request(
        string $url,
        array $body = [],
        array $headers = [],
        string $method = 'GET',
    ): array {
        $curl = curl_init();
        $curlHeaders = [];
        foreach ($headers as $header => $value) {
            $curlHeaders[] = "$header: $value";
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $curlHeaders
        ));

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl)['http_code'];

        if ($statusCode >= 500 || curl_error($curl)) {
            throw new BillingUnavailableException('Service is temporarily unavailable. Try again later.');
        }
        curl_close($curl);

        return [
            'data' => $response,
            'statusCode' => $statusCode,
        ];
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     * @throws InvalidCredentialsException
     */
    public function auth(string $username, string $password): array
    {
        $response = $this->request(
            $this->billingUrl . '/api/v1/auth',
            [
                'email' => $username,
                'password' => $password
            ],
            ['Content-Type' => 'application/json'],
            'POST'
        );

        if ($response['statusCode'] === 400 || $response['statusCode'] === 401) {
            $errorMsg = $data['error'] ?? 'Неверный логин или пароль.';
            throw new InvalidCredentialsException($errorMsg);
        }

        return json_decode($response['data'], true, 512, JSON_THROW_ON_ERROR);

    }

    /**
     * @throws InvalidCredentialsException
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    public function register(string $username, string $password): array
    {
        $response = $this->request(
            $this->billingUrl . '/api/v1/register',
            [
                'email' => $username,
                'password' => $password
            ],
            ['Content-Type' => 'application/json'],
            'POST'
        );

        $data = json_decode($response['data'], true, 512, JSON_THROW_ON_ERROR);

        if ($response['statusCode'] === 400 || $response['statusCode'] === 401) {
            $errorMsg = $data['error'] ?? 'Неверный логин или пароль.';
            throw new InvalidCredentialsException($errorMsg);
        }

        $user = new User();
        $user->setApiToken($data['access_token']);
        $user->setRefreshToken($data['refresh_token']);

        return $data;
    }



    /**
     * @throws BillingUnavailableException
     */
    public function getCurrentUser(string $token): User
    {
        $response = $this->request(
            $this->billingUrl . '/api/v1/users/current',
            [],
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'GET'
        );

        $userData = json_decode($response['data'], true, 512, JSON_THROW_ON_ERROR);

        if ($response['statusCode'] === 401 || $response['statusCode'] === 404) {
            throw new AuthenticationException($userData['message']);
        }

        if ($response['statusCode'] === 500) {
            throw new BillingUnavailableException('Service is temporarily unavailable. Try again later.');
        }

        $user = new User();
        $user->setApiToken($token);
        $user->setEmail($userData['username']);
        $user->setRoles($userData['roles']);
        $user->setBalance($userData['balance']);


        return $user;
    }

    public function refreshToken(User $user): User
    {
        $response = $this->request(
            $this->billingUrl . 'token/refresh',
            [
                'refresh_token' => $user->getRefreshToken()
            ],
            [
                'Content-Type' => 'application/json',
            ],
            'POST'
        );

        $tokenData = json_decode($response['data'], true, 512, JSON_THROW_ON_ERROR);

        $user->setRefreshToken($tokenData['refresh_token']);
        $user->setApiToken($tokenData['token']);

        return $user;
    }


    public function coursesList(): array|null
    {
        $response = $this->request(
            $this->billingUrl . '/api/v1/courses',
            [],
            [
                'Content-Type' => 'application/json',
            ],
            'GET'
        );

        $coursesData = json_decode($response['data'], true);

        if ($response['statusCode'] == 500) {
            throw new BillingUnavailableException('Service is temporarily unavailable. Try again later.');
        }

        return $coursesData;
    }

    public function payCourse(string $token, string $courseCode): bool
    {
        if ($token == null) {
            throw new InvalidCredentialsException("Missing token");
        }

        $response = $this->request(
            $this->billingUrl . '/api/v1/courses/' . $courseCode . '/pay',
            [],
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'POST'
        );

        if ($response['statusCode'] == 406) {
            throw new NotEnoughBalanceException();
        } elseif ($response['statusCode'] == 500) {
            throw new BillingUnavailableException();
        }

        $paymentData = json_decode($response['data'], true);

        if ($paymentData['success'] == true) {
            return true;
        } else {
            throw new BillingUnavailableException();
        }
    }


    public function courseInfoByCode(string $courseCode): array
    {
        $response = $this->request(
            $this->billingUrl . '/api/v1/courses/' . $courseCode,
            [],
            [
                'Content-Type' => 'application/json',
            ],
            'GET'
        );

        $courseData = json_decode($response['data'], true);

        if ($response['statusCode'] == 404) {
            throw new BillingUnavailableException($courseData['error']);
        } elseif ($response['statusCode'] == 500) {
            throw new BillingUnavailableException('Service is temporarily unavailable. Try again later.');
        }

        return $courseData;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function isEnoughBalance(string $token, string $courseCode): bool
    {
        if ($token == null) {
            return false;
        }

        $user = $this->getCurrentUser($token);
        $courseData = $this->courseInfoByCode($courseCode);

        return $courseData['type'] === 'free' || $courseData['price'] <= $user->getBalance();
    }


    /**
     * @throws \DateMalformedStringException
     * @throws BillingUnavailableException
     */
    public function isCourseAvailable(string $token, string $courseCode): bool|string
    {
        if ($token == null) {
            return false;
        }

        $response = $this->request(
            $this->billingUrl . '/api/v1/transactions?filter[course_code]=' . $courseCode,
            [],
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'GET'
        );

        if ($response['statusCode'] == 500) {
            throw new BillingUnavailableException('Service is temporarily unavailable. Try again later.');
        }

        $transactionsData = json_decode($response['data'], true);

        if (count($transactionsData) > 0) {
            $lastTransaction = $this->getLatestTransaction($transactionsData);
            if (isset($lastTransaction['expires_at'])) {    // Проверка для арендуемых курсов
                $expiresAt = new DateTime($lastTransaction['expires_at']);
                $now = new DateTime();
                if ($expiresAt >= $now) {
                    return $expiresAt->format('d.m.Y');
                } else {
                    return false;
                }
            } else {    // Если покупаемый курс - достаточно знать что транзакция покупки была
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function getLatestTransaction(array $transactions): array
    {
        if (empty($transactions)) {
            return [];
        }

        $latest = null;
        $latestDate = null;

        foreach ($transactions as $transaction) {
            $currentDate = new DateTime($transaction['created_at']);

            if ($latestDate === null || $currentDate > $latestDate) {
                $latest = $transaction;
                $latestDate = $currentDate;
            }
        }

        return $latest;
    }


    public function getUserTransactions(string $token): array
    {
        if ($token == null) {
            throw new Exception("Missing token");
        }

        $response = $this->request(
            $this->billingUrl . '/api/v1/transactions',
            [],
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'GET'
        );

        if ($response['statusCode'] == 500) {
            throw new BillingUnavailableException('Service is temporarily unavailable. Try again later.');
        }

        $transactionsData = json_decode($response['data'], true);

        if (!$transactionsData) {
            throw new BillingUnavailableException('Service is temporarily unavailable. Try again later.');
        }

        // Сортировка
        usort($transactionsData, function ($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        return $transactionsData;
    }


}