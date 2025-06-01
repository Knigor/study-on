<?php

namespace App\Tests;


use App\Tests\Mock\BillingClientMock;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class AuthControllerTest extends WebTestCase
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

        //  echo $client->getResponse()->getContent();
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


        echo $client->getResponse()->getContent();

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

        echo $client->getResponse()->getContent();

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

}
