<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LessonControllerTest extends WebTestCase
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

    // Тестируем создание урока пользователем
    public function testCreateLesson(): void
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

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // открываем страницу курса
        $crawler = self::$client->request('GET', '/courses/' . $course->getId());
        self::assertResponseIsSuccessful();

        // Перед отправкой формы считаем количество уроков
        $lessonCountBefore = $entityManager->getRepository(Lesson::class)->count(['course' => $course]);

        // Нажимаем кнопку "Добавить урок"
        $addLessonLink = $crawler->selectLink('Добавить урок')->link();
        $crawler = self::$client->click($addLessonLink);
        self::assertResponseIsSuccessful();

        // заполняем форму на странице добавления урока
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'Описание нового урока',
            'lesson[orderNumber]' => '4000',
        ]);
        self::$client->submit($form);

        // редирект обратно на страницу с курсами
        self::$client->followRedirect();
        self::assertRouteSame('app_course_show', ['id' => $course->getId()]);
        self::assertResponseIsSuccessful();

        // Проверяем, что урок создался
        $lessonCountAfter = $entityManager->getRepository(Lesson::class)->count(['course' => $course]);
        $this->assertSame($lessonCountBefore + 1, $lessonCountAfter);
    }

    // тестируем на ошибку создания урока
    public function testCreateLessonError(): void
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

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // открываем страницу курса
        $crawler = self::$client->request('GET', '/courses/' . $course->getId());
        self::assertResponseIsSuccessful();
        // Нажимаем кнопку "Добавить урок"
        $addLessonLink = $crawler->selectLink('Добавить урок')->link();
        $crawler = self::$client->click($addLessonLink);
        self::assertResponseIsSuccessful();
        // передаем неправильный number
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'абоба',
            'lesson[orderNumber]' => 100001,
        ]);
        self::$client->submit($form);
        self::assertResponseStatusCodeSame(422);

        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'This value should be 10000 or less.'
        );

        // передаем меньше 3 символов в lessonContent
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'f',
            'lesson[orderNumber]' => 1000,
        ]);
        self::$client->submit($form);
        self::assertResponseStatusCodeSame(422);

        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Описание должно содержать минимум 3 символа.'
        );


        // передаем меньше 3 символов в nameLesson
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => 'bb',
            'lesson[lessonContent]' => 'Описание мега крутого',
            'lesson[orderNumber]' => 1000,
        ]);
        self::$client->submit($form);
        self::assertResponseStatusCodeSame(422);

        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название урока должно содержать минимум 3 символа.'
        );


    }

    // тестируем удаление урока
    public function testDeleteLesson(): void
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

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // достаем урок
        $lesson = $entityManager->getRepository(Lesson::class)->findOneBy(['course' => $course]);

        // открываем страницу урока
        $crawler = self::$client->request('GET', '/lessons/' . $lesson->getId());
        self::assertResponseIsSuccessful();

        // Перед отправкой формы считаем количество уроков
        $lessonCountBefore = $entityManager->getRepository(Lesson::class)->count(['course' => $course]);

        // Нажимаем кнопку "Удалить урок" а затем редирект
        $form = $crawler->selectButton('Удалить урок')->form();
        self::$client->submit($form);
        self::$client->followRedirect();
        self::assertResponseIsSuccessful();

        // проверяем что на нужно странице
        self::assertRouteSame('app_course_show', ['id' => $course->getId()]);
        self::assertResponseIsSuccessful();

        // Проверяем, что урок удален
        $lessonCountAfter = $entityManager->getRepository(Lesson::class)->count(['course' => $course]);
        $this->assertSame($lessonCountBefore - 1, $lessonCountAfter);
    }

    // тестируем редактировать урок
    public function testEditLesson(): void
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

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // достаем урок
        $lesson = $entityManager->getRepository(Lesson::class)->findOneBy(['course' => $course]);
        // сохраняем id урока
        $lessonId = $lesson->getId();

        // открываем страницу редактирования урока
        $crawler = self::$client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        self::assertResponseIsSuccessful();

        // заполняем форму на странице редактирования урока и редиректим на страницу с курсами
        $form = $crawler->selectButton('Обновить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'Описание нового урока',
            'lesson[orderNumber]' => 4000,
        ]);
        self::$client->submit($form);
        self::$client->followRedirect();
        self::assertResponseIsSuccessful();

        // очищаем кеш бд, чтобы получить новые данные из бд
        $entityManager->clear();

        // достаем урок снова по ID
        $lesson = $entityManager->getRepository(Lesson::class)->find($lessonId);

        // проверяем что урок обновился
        $this->assertSame('Новый урок', $lesson->getNameLesson());
        $this->assertSame('Описание нового урока', $lesson->getLessonContent());
        $this->assertSame(4000, $lesson->getOrderNumber());

    }

    // тестируем на ошибку редактирования
    public function testEditLessonError(): void
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

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // достаем урок
        $lesson = $entityManager->getRepository(Lesson::class)->findOneBy(['course' => $course]);
        // сохраняем id урока
        $lessonId = $lesson->getId();

        // открываем страницу редактирования урока
        $crawler = self::$client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        self::assertResponseIsSuccessful();

        // передаем неправильный number
        $form = $crawler->selectButton('Обновить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'абоба',
            'lesson[orderNumber]' => 100001,
        ]);
        self::$client->submit($form);
        self::assertResponseStatusCodeSame(422);

        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'This value should be 10000 or less.'
        );


        // передаем меньше 3 символов в lessonContent
        $form = $crawler->selectButton('Обновить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'f',
            'lesson[orderNumber]' => 1000,
        ]);
        self::$client->submit($form);
        self::assertResponseStatusCodeSame(422);

        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Описание должно содержать минимум 3 символа.'
        );


        // передаем меньше 3 символов в nameLesson
        $form = $crawler->selectButton('Обновить')->form([
            'lesson[nameLesson]' => 'bb',
            'lesson[lessonContent]' => 'Описание мега крутого',
            'lesson[orderNumber]' => 1000,
        ]);
        self::$client->submit($form);
        self::assertResponseStatusCodeSame(422);

        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название урока должно содержать минимум 3 символа.'
        );

    }

}