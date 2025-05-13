<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserRegisterDto
{
    #[Assert\NotBlank(message: "Введите email")]
    #[Assert\Email(message: "Некорректный email")]
    public string $email;

    #[Assert\NotBlank(message: "Введите пароль")]
    #[Assert\Length(min: 6, minMessage: "Пароль должен быть длиннее 6 символов")]
    public string $password;

    #[Assert\NotBlank(message: "Подтвердите пароль")]
    #[Assert\EqualTo(propertyPath: "password", message: "Пароли не совпадают")]
    public string $confirmPassword;
}