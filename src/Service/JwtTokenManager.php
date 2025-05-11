<?php

namespace App\Service;

use App\Exception\JwtManagerException;

class JwtTokenManager
{
    private int $reserveTimeCheck = 5;

    private function decode(string $token): array
    {
        if (count(explode('.', $token)) < 3){
            throw new JwtManagerException(message: "Не корректный jwt токен");
        }
        $jwtArr = array_combine(['header', 'payload', 'hash'], explode('.', $token));
        return json_decode(base64_decode($jwtArr['payload']), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JwtManagerException
     * @throws \JsonException
     */
    public function isExpired(string $token): mixed
    {
        $payload = $this->decode($token);

        $exp = (int) $payload['exp'];
        $dateTimeNow = (new \DateTime())->getTimestamp();
        $timestampReserve = $this->reserveTimeCheck * 60;
        $timeNowWithReserve = $dateTimeNow + $timestampReserve;

        return $exp < $timeNowWithReserve;
    }
}