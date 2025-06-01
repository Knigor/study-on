<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class CourseControllerTest extends WebTestCase
{

    protected static ?\Symfony\Bundle\FrameworkBundle\KernelBrowser $client = null;

    protected function setUp(): void
    {
        self::$client = static::createClient();
        self::$client->disableReboot();
        self::$client->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('http://billingURL')
        );

        $this->loadFixtures($this->getFixtures());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        self::$client = null;
    }

    protected function getFixtures(): array
    {
        return [
            CourseFixtures::class,
        ];
    }

    protected function loadFixtures(array $fixtures = []): void
    {
        $loader = new Loader();

        foreach ($fixtures as $fixture) {
            if (!is_object($fixture)) {
                $fixture = new $fixture();
            }

            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer(self::$client->getContainer());
            }

            $loader->addFixture($fixture);
        }

        $entityManager = self::getEntityManager();
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }

    protected static function getEntityManager(): ObjectManager
    {
        return self::$client->getContainer()->get('doctrine')->getManager();
    }


    public function testCourse(): void
    {
        $entityManager = self::getEntityManager();
        $courses = $entityManager->getRepository(Course::class)->findAll();

        self::$client->request('GET', '/courses');

        self::assertResponseIsSuccessful();
        $this->assertCount(count($courses), self::$client->getCrawler()->filter('.course-item'));

        echo self::$client->getResponse()->getContent();
    }
    // проверяем на не существующий курс
    public function testShowNoCourseFound(): void
    {


        self::$client->request('GET', '/courses/9999999');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // создаем новый курс

    public function testCreateCourse(): void
    {
        $entityManager = self::getEntityManager();

        // логинимся
        // Переход на страницу курсов
        $crawler = self::$client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Переход на страницу логина
        $authFormLink = $crawler->selectLink('Войти')->link();
        $crawler = self::$client->click($authFormLink);
        $this->assertEquals('/login', self::$client->getRequest()->getPathInfo());

        // Отправка формы логина
        $submitBtn = $crawler->selectButton('Войти');
        $loginForm = $submitBtn->form([
            'email' => 'admin@mail.ru', // под админкой!!!
            'password' => '123456',
        ]);
        self::$client->submit($loginForm);

        // Переход после редиректа
        $crawler = self::$client->followRedirect();
        self::assertResponseIsSuccessful();

        // Переход на страницу создания курса

        $link = $crawler->selectLink('Создать новый курс')->link();
        $crawler = self::$client->click($link);
        $this->assertEquals('/courses/new', self::$client->getRequest()->getPathInfo());

        self::assertResponseIsSuccessful();

        // Создаем курс
        $form = $crawler->selectButton('Сохранить')->form([
            'course[characterCode]' => 'new-course',
            'course[name]' => 'Новый курс',
            'course[description]' => 'Описание нового курса'
        ]);
        self::$client->submit($form);

        // редирект
        $this->assertSame(self::$client->getResponse()->headers->get('location'), '/courses');
        self::$client->followRedirect();
        self::assertResponseIsSuccessful();

        // Теперь получаем список курсов
        $crawler = self::$client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Находим последний добавленный курс, проверяя по названию и по описанию
        $lastCourse = $crawler->filter('.course-name')->last();
        $this->assertSame('Новый курс', $lastCourse->text());

        $courseDescription = $crawler->filter('.course-description')->last();
        $this->assertSame('Описание нового курса', $courseDescription->text());

    }

    public function testCreateCourseError(): void
    {
        // логинимся
        // Переход на страницу курсов
        $crawler = self::$client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Переход на страницу логина
        $authFormLink = $crawler->selectLink('Войти')->link();
        $crawler = self::$client->click($authFormLink);
        $this->assertEquals('/login', self::$client->getRequest()->getPathInfo());

        // Отправка формы логина
        $submitBtn = $crawler->selectButton('Войти');
        $loginForm = $submitBtn->form([
            'email' => 'admin@mail.ru', // под админкой!!!
            'password' => '123456',
        ]);
        self::$client->submit($loginForm);

        // Переход после редиректа
        $crawler = self::$client->followRedirect();
        self::assertResponseIsSuccessful();

        // Переход на страницу создания курса

        $link = $crawler->selectLink('Создать новый курс')->link();
        $crawler = self::$client->click($link);
        $this->assertEquals('/courses/new', self::$client->getRequest()->getPathInfo());

        self::assertResponseIsSuccessful();

        // В коде допускаем ошибку
        $form = $crawler->selectButton('Сохранить')->form([
            'course[characterCode]' => 'ff',
            'course[name]' => 'Новый курс',
            'course[description]' => 'Описание нового курса'
        ]);
        self::$client->submit($form);
        self::assertResponseStatusCodeSame(422);


        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Код курса должен содержать минимум 3 символа.'
        );

        // Допускаем ошибку в описании
        $form = $crawler->selectButton('Сохранить')->form([
            'course[characterCode]' => 'new-course',
            'course[name]' => 'Новый курс',
            'course[description]' => 'ff'
        ]);
        self::$client->submit($form);
        self::assertResponseStatusCodeSame(422);


        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Описание должно содержать минимум 3 символа.'
        );

    }

    // тест на успешное редактирование курса
    public function testEditCourse(): void
    {
        $entityManager = self::getEntityManager();


        // логинимся
        // Переход на страницу курсов
        $crawler = self::$client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Переход на страницу логина
        $authFormLink = $crawler->selectLink('Войти')->link();
        $crawler = self::$client->click($authFormLink);
        $this->assertEquals('/login', self::$client->getRequest()->getPathInfo());

        // Отправка формы логина
        $submitBtn = $crawler->selectButton('Войти');
        $loginForm = $submitBtn->form([
            'email' => 'admin@mail.ru', // под админкой!!!
            'password' => '123456',
        ]);
        self::$client->submit($loginForm);

        // Переход после редиректа
        $crawler = self::$client->followRedirect();
        self::assertResponseIsSuccessful();

        // переходим на первый курс
        $link = $crawler->filter('.course-item')->link();
        $crawler = self::$client->click($link);
        self::assertResponseIsSuccessful();

        // получаем ID курса до редактирования
        $courseId = self::$client->getRequest()->attributes->get('id');


        // открываем страницу редактирования курса
        $editLink = $crawler->selectLink('Редактировать курс')->link();
        $crawler = self::$client->click($editLink);
        self::assertResponseIsSuccessful();


        // заполняем форму на странице редактирования курса и получаем id
        $form = $crawler->selectButton('Сохранить')->form([
            'course[characterCode]' => 'new-course',
            'course[name]' => 'Новый курс',
            'course[description]' => 'Описание нового курса'
        ]);
        self::$client->submit($form);

        // редирект
        $crawler = self::$client->followRedirect();
        self::assertRouteSame('app_course_show', ['id' => $courseId]);
        self::assertResponseIsSuccessful();

        // проверяем, что данные обновились
        $this->assertSame($crawler->filter('.text-center')->text(), 'Новый курс');

    }


    // тест на успешное удаление курса
    public function testDeleteCourse(): void
    {
        // логинимся
        // Переход на страницу курсов
        $crawler = self::$client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // Переход на страницу логина
        $authFormLink = $crawler->selectLink('Войти')->link();
        $crawler = self::$client->click($authFormLink);
        $this->assertEquals('/login', self::$client->getRequest()->getPathInfo());

        // Отправка формы логина
        $submitBtn = $crawler->selectButton('Войти');
        $loginForm = $submitBtn->form([
            'email' => 'admin@mail.ru', // под админкой!!!
            'password' => '123456',
        ]);
        self::$client->submit($loginForm);

        // Переход после редиректа
        $crawler = self::$client->followRedirect();
        self::assertResponseIsSuccessful();

        $entityManager = self::getEntityManager();

        // список курсов
        $crawler = self::$client->request('GET', '/courses');
        self::assertResponseIsSuccessful();

        // сохраняем кол-во курсов до удаления
        $coursesCountBefore = count($entityManager->getRepository(Course::class)->findAll());

        // Находим и кликаем кнопку "Удалить" у первого курса
        $deleteForm = $crawler->filter('.delete-button')->first()->form();
        self::$client->submit($deleteForm);

        // Проверяем редирект после удаления
        self::assertResponseRedirects();
        $crawler = self::$client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertRouteSame('app_course_index');

        // проверяем что курс удален
        $coursesCountAfter = count($entityManager->getRepository(Course::class)->findAll());
        $this->assertSame($coursesCountAfter, $coursesCountBefore - 1);
    }

}
