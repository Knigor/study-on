<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;

class LessonControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    // Тестируем создание урока пользователем
    public function testCreateLesson(): void
    {
        $client = self::getClient();
        $entityManager = self::getEntityManager();

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // открываем страницу курса
        $crawler = $client->request('GET', '/courses/' . $course->getId());
        $this->assertResponseOk();

        // Перед отправкой формы считаем количество уроков
        $lessonCountBefore = $entityManager->getRepository(Lesson::class)->count(['course' => $course]);

        // Нажимаем кнопку "Добавить урок"
        $addLessonLink = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($addLessonLink);
        $this->assertResponseOk();

        // заполняем форму на странице добавления урока
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'Описание нового урока',
            'lesson[orderNumber]' => '4000',
        ]);
        $client->submit($form);

        // редирект обратно на страницу с курсами
        $client->followRedirect();
        self::assertRouteSame('app_course_show', ['id' => $course->getId()]);
        $this->assertResponseOk();

        // Проверяем, что урок создался
        $lessonCountAfter = $entityManager->getRepository(Lesson::class)->count(['course' => $course]);
        $this->assertSame($lessonCountBefore + 1, $lessonCountAfter);
    }

    // тестируем на ошибку создания урока
    public function testCreateLessonError(): void
    {
        $client = self::getClient();
        $entityManager = self::getEntityManager();

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // открываем страницу курса
        $crawler = $client->request('GET', '/courses/' . $course->getId());
        $this->assertResponseOk();

        // Нажимаем кнопку "Добавить урок"
        $addLessonLink = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($addLessonLink);
        $this->assertResponseOk();

        // передаем неправильный number
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'абоба',
            'lesson[orderNumber]' => 100001,
        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        self::assertSelectorTextContains(
            'div',
            'This value should be 10000 or less.'
        );

        // передаем меньше 3 символов в lessonContent
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'f',
            'lesson[orderNumber]' => 1000,
        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        self::assertSelectorTextContains(
            'div',
            'Описание должно содержать минимум 3 символа.'
        );

        // передаем меньше 3 символов в nameLesson
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[nameLesson]' => 'bb',
            'lesson[lessonContent]' => 'Описание мега крутого',
            'lesson[orderNumber]' => 1000,
        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        self::assertSelectorTextContains(
            'div',
            'Название урока должно содержать минимум 3 символа.'
        );
    }

    // тестируем удаление урока
    public function testDeleteLesson(): void
    {
        $client = self::getClient();
        $entityManager = self::getEntityManager();

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // достаем урок
        $lesson = $entityManager->getRepository(Lesson::class)->findOneBy(['course' => $course]);

        // открываем страницу урока
        $crawler = $client->request('GET', '/lessons/' . $lesson->getId());
        $this->assertResponseOk();

        // Перед отправкой формы считаем количество уроков
        $lessonCountBefore = $entityManager->getRepository(Lesson::class)->count(['course' => $course]);

        // Нажимаем кнопку "Удалить урок" а затем редирект
        $form = $crawler->selectButton('Удалить урок')->form();
        $client->submit($form);
        $client->followRedirect();
        $this->assertResponseOk();

        // проверяем что на нужно странице
        self::assertRouteSame('app_course_show', ['id' => $course->getId()]);
        $this->assertResponseOk();

        // Проверяем, что урок удален
        $lessonCountAfter = $entityManager->getRepository(Lesson::class)->count(['course' => $course]);
        $this->assertSame($lessonCountBefore - 1, $lessonCountAfter);
    }

    // тестируем редактировать урок
    public function testEditLesson(): void
    {
        $client = self::getClient();
        $entityManager = self::getEntityManager();

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // достаем урок
        $lesson = $entityManager->getRepository(Lesson::class)->findOneBy(['course' => $course]);
        // сохраняем id урока
        $lessonId = $lesson->getId();

        // открываем страницу редактирования урока
        $crawler = $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseOk();

        // заполняем форму на странице редактирования урока и редиректим на страницу с курсами
        $form = $crawler->selectButton('Обновить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'Описание нового урока',
            'lesson[orderNumber]' => 4000,
        ]);
        $client->submit($form);
        $client->followRedirect();
        $this->assertResponseOk();

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
        $client = self::getClient();
        $entityManager = self::getEntityManager();

        // достаем курс
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);

        // достаем урок
        $lesson = $entityManager->getRepository(Lesson::class)->findOneBy(['course' => $course]);
        // сохраняем id урока
        $lessonId = $lesson->getId();

        // открываем страницу редактирования урока
        $crawler = $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseOk();

        // передаем неправильный number
        $form = $crawler->selectButton('Обновить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'абоба',
            'lesson[orderNumber]' => 100001,
        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        self::assertSelectorTextContains(
            'div',
            'This value should be 10000 or less.'
        );

        // передаем меньше 3 символов в lessonContent
        $form = $crawler->selectButton('Обновить')->form([
            'lesson[nameLesson]' => 'Новый урок',
            'lesson[lessonContent]' => 'f',
            'lesson[orderNumber]' => 1000,
        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        self::assertSelectorTextContains(
            'div',
            'Описание должно содержать минимум 3 символа.'
        );

        // передаем меньше 3 символов в nameLesson
        $form = $crawler->selectButton('Обновить')->form([
            'lesson[nameLesson]' => 'bb',
            'lesson[lessonContent]' => 'Описание мега крутого',
            'lesson[orderNumber]' => 1000,
        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        self::assertSelectorTextContains(
            'div',
            'Название урока должно содержать минимум 3 символа.'
        );
    }

}