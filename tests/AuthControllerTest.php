<?php

namespace App\Tests;


use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Service\BillingClient;

class AuthControllerTest extends WebTestCase
{
    // подключаем моковый биллинг
    private function createMockedClient(bool $ex = false): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        $mock = new BillingClientMock($ex);
        $client->getContainer()->set(BillingClient::class, $mock);

        return $client;
    }
    // регистрируем пользователя
    public function testSuccessfulRegistration(): void
    {
        // создаем мок
        $client = $this->createMockedClient();
        // получаем список курсов
        $crawler = $client->request('GET', '/courses');
        // нажимаем кнопку "Регистрация"
        /** @noinspection PhpUnusedLocalVariableInspection */
        $crawler = $client->clickLink('Регистрация');
        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('h1', 'Регистрация');

        // отправляем пользователя
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'new@example.com';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '123456';
        $client->submit($form);

        self::assertResponseRedirects('/courses');
    }

    // пользователь уже есть
    public function testRegisterEmailExists(): void
    {
        // создаем мок
        $client = $this->createMockedClient();
        // получаем список курсов
        $crawler = $client->request('GET', '/courses');
        // нажимаем кнопку "Регистрация"
        /** @noinspection PhpUnusedLocalVariableInspection */
        $crawler = $client->clickLink('Регистрация');
        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('h1', 'Регистрация');

        // отправляем пользователя
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'admin@mail.ru';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '123456';
        $client->submit($form);

        self::assertSelectorTextContains('div.alert.alert-danger', 'User with this email already exists');

    }


    // плохие валидные данные

    public function testRegisterWithNotValidData(): void
    {
        // создаем мок
        $client = $this->createMockedClient();
        // получаем список курсов
        $crawler = $client->request('GET', '/courses');
        // нажимаем кнопку "Регистрация"
        /** @noinspection PhpUnusedLocalVariableInspection */
        $crawler = $client->clickLink('Регистрация');
        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('h1', 'Регистрация');

        // отправляем пользователя при некорректном email
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'aboba@mal';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '123456';
        $client->submit($form);
        self::assertAnySelectorTextContains('div.invalid-feedback', 'Некорректный email');

        // При не совпадающих паролях
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'aboba@mail.ru';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '123456789';
        $client->submit($form);
        self::assertAnySelectorTextContains('div.invalid-feedback', 'Пароли не совпадают');

        // При пустом email
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = '';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '123456';
        $client->submit($form);
        self::assertAnySelectorTextContains('div.invalid-feedback', 'Введите email');

        // При пустом пароле
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'aboba@mail.ru';
        $form['register[password]'] = '';
        $form['register[confirmPassword]'] = '';
        $client->submit($form);
        self::assertAnySelectorTextContains('div.invalid-feedback', 'Введите пароль');

        // При пустом подтверждении пароля
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'aboba@mail.ru';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '';
        $client->submit($form);
        self::assertAnySelectorTextContains('div.invalid-feedback', 'Подтвердите пароль');
    }



    public function testRegisterNotWorkingBilling(): void
    {
        // создаем мок
        $client = $this->createMockedClient(true);
        // получаем список курсов
        $crawler = $client->request('GET', '/courses');
        // нажимаем кнопку "Регистрация"
        /** @noinspection PhpUnusedLocalVariableInspection */
        $crawler = $client->clickLink('Регистрация');
        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('h1', 'Регистрация');

        // отправляем пользователя
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'admin111111@mail.ru';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '123456';
        $client->submit($form);

        self::assertSelectorTextContains('div.alert.alert-danger', 'Сервис временно недоступен. Попробуйте зарегистироваться позже.');

    }


    // авторизация

    public function testSuccessAdminLogin(): void
    {
        // создаем мок
        $client = $this->createMockedClient();
        // получаем список курсов
        $crawler = $client->request('GET', '/courses');
        // нажимаем кнопку Войти
        /** @noinspection PhpUnusedLocalVariableInspection */
        $crawler = $client->clickLink('Войти');
        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('h1', 'Войти');

        // отправляем пользователя
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = 'admin@mail.ru';
        $form['password'] = '123456';
        $client->submit($form);



        $crawler = $client->followRedirect(); // переходим по нему
        self::assertResponseIsSuccessful();   // проверяем, что страница /courses загружается

    }

}

//