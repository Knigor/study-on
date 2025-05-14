<?php

namespace App\Tests;

use App\Tests\Mock\BillingClientMock;
use App\Service\BillingClient;

class AuthControllerTest extends AbstractTest
{
    public function testAdminLogin(): void
    {
        $client = $this->replaceServiceBillingClient($ex = false);
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form();
        $form['email']->setValue('admin@mail.com');
        $form['password']->setValue('123456');


        $client->submit($form);
        self::assertResponseRedirects('/courses');


    }

}