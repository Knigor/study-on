<?php

namespace App\Tests;


use App\Tests\Mock\BillingClientMock;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class SecurityControllerTest extends WebTestCase
{

    public function testLoginSuccessful(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );

        // Переход на страницу курсов
        $crawler = $client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Переход на страницу логина
        $authFormLink = $crawler->selectLink('Войти')->link();
        $crawler = $client->click($authFormLink);
        $this->assertEquals('/login', $client->getRequest()->getPathInfo());

        // Отправка формы логина
        $submitBtn = $crawler->selectButton('Войти');
        $loginForm = $submitBtn->form([
            'email' => 'user@mail.ru',
            'password' => '123456',
        ]);
        $client->submit($loginForm);

        // Переход после редиректа
        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();

        // Теперь повторно ищем ссылку на "Профиль" уже в авторизованной версии страницы
        $profileLink = $crawler->selectLink('Профиль')->link();
        $crawler = $client->click($profileLink);

        // Проверяем, что перешли на /profile
        $this->assertEquals('/profile', $client->getRequest()->getPathInfo());
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('li', 'user@mail.ru');


    }

    public function testLoginFailedEmail(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );

        // Переход на страницу курсов
        $crawler = $client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Переход на страницу логина
        $authFormLink = $crawler->selectLink('Войти')->link();
        $crawler = $client->click($authFormLink);
        $this->assertEquals('/login', $client->getRequest()->getPathInfo());

        // Отправка формы логина
        $submitBtn = $crawler->selectButton('Войти');
        $loginForm = $submitBtn->form([
            'email' => 'lolkek@mail.ru',
            'password' => '123456',
        ]);
        $client->submit($loginForm);




        // проверяем корректный вывод сообщения об ошибке
        $client->followRedirect();
        self::assertSelectorTextContains(
            '.alert.alert-danger',
            'An authentication exception occurred.'
        );


    }

    public function testLoginFailedPassword(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );

        // Переход на страницу курсов
        $crawler = $client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Переход на страницу логина
        $authFormLink = $crawler->selectLink('Войти')->link();
        $crawler = $client->click($authFormLink);
        $this->assertEquals('/login', $client->getRequest()->getPathInfo());

        // Отправка формы логина
        $submitBtn = $crawler->selectButton('Войти');
        $loginForm = $submitBtn->form([
            'email' => 'user@mail.ru',
            'password' => '12345',
        ]);
        $client->submit($loginForm);

        // проверяем корректный вывод сообщения об ошибке
        $client->followRedirect();
        self::assertSelectorTextContains(
            '.alert.alert-danger',
            'An authentication exception occurred.'
        );
    }

    public function testRegister(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );

        $crawler = $client->request('GET', '/register');
        self::assertResponseIsSuccessful();


        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'userNew@mail.ru';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '123456';
        $client->submit($form);


        // Переход после редиректа
        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();

        // Теперь повторно ищем ссылку на "Профиль" уже в авторизованной версии страницы
        $profileLink = $crawler->selectLink('Профиль')->link();
        $crawler = $client->click($profileLink);



    }

    public function testFailedRegisterEmail(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );

        $crawler = $client->request('GET', '/register');
        self::assertResponseIsSuccessful();


        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'user@mail.ru';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '123456';
        $client->submit($form);


        // проверяем корректный вывод ошибки
        self::assertSelectorTextContains(
            '.alert.alert-danger',
            'User with this email already exists'
        );
    }


    public function testFailedConfirmPassword(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );

        $crawler = $client->request('GET', '/register');
        self::assertResponseIsSuccessful();


        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['register[email]'] = 'user@mail.ru';
        $form['register[password]'] = '123456';
        $form['register[confirmPassword]'] = '123457';
        $client->submit($form);


        // проверяем корректный вывод ошибки
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Пароли не совпадают'
        );
    }


    // тесты для профиля

    public function testProfileLogin(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );

        // Переход на страницу курсов
        $crawler = $client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Переход на страницу логина
        $authFormLink = $crawler->selectLink('Войти')->link();
        $crawler = $client->click($authFormLink);
        $this->assertEquals('/login', $client->getRequest()->getPathInfo());

        // Отправка формы логина
        $submitBtn = $crawler->selectButton('Войти');
        $loginForm = $submitBtn->form([
            'email' => 'user@mail.ru',
            'password' => '123456',
        ]);
        $client->submit($loginForm);

        // Переход после редиректа
        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();

        // Теперь повторно ищем ссылку на "Профиль" уже в авторизованной версии страницы
        $profileLink = $crawler->selectLink('Профиль')->link();
        $crawler = $client->click($profileLink);

        // Проверяем, что перешли на /profile
        $this->assertEquals('/profile', $client->getRequest()->getPathInfo());
        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('.username', 'user@mail.ru');
        self::assertSelectorTextContains('.balance', 'Баланс: 500.1');
        self::assertSelectorTextContains('.role', 'ROLE_USER');

    }


    public function testProfileLogout(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );

        // Переход на страницу курсов
        $crawler = $client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Переход на страницу логина
        $authFormLink = $crawler->selectLink('Войти')->link();
        $crawler = $client->click($authFormLink);
        $this->assertEquals('/login', $client->getRequest()->getPathInfo());

        // Отправка формы логина
        $submitBtn = $crawler->selectButton('Войти');
        $loginForm = $submitBtn->form([
            'email' => 'user@mail.ru',
            'password' => '123456',
        ]);
        $client->submit($loginForm);

        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();

        // Находим и нажимаем кнопку "Выйти"
        $logoutLink = $crawler->selectLink('Выйти')->link();
        $client->click($logoutLink);
        $this->assertEquals('/logout', $client->getRequest()->getPathInfo());
        $crawler = $client->followRedirect();
        $this->assertEquals('/', $client->getRequest()->getPathInfo());
        $crawler = $client->followRedirect();



        // проверяем на кнопки войти и регистрации

        self::assertEquals('/courses', $client->getRequest()->getPathInfo());
        self::assertEquals(1, $crawler->selectLink('Войти')->count());
        self::assertEquals(1, $crawler->selectLink('Регистрация')->count());
    }

    public function testRedirectWithoutLogin(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );
        $crawler = $client->request('GET', '/profile');
        self::assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertEquals('/login', $client->getRequest()->getPathInfo());


    }
}
