<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    // Проверка корректных статусов GET и содержимого ответа
    public function testGetActionsResponseOk(): void
    {
        $client = self::getClient();
        $entityManager = self::getEntityManager();
        $courses = $entityManager->getRepository(Course::class)->findAll();

        // Проверка списка всех курсов
        $client->request('GET', '/courses');
        $this->assertResponseOk();
        $this->assertCount(count($courses), $client->getCrawler()->filter('.course-item'));

        foreach ($courses as $course) {
            // Проверка страницы конкретного курса
            $client->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();

            // Проверка количества уроков на странице курса
            $lessons = $entityManager->getRepository(Lesson::class)->findBy(['course' => $course]);
            $this->assertCount(count($lessons), $client->getCrawler()->filter('.lesson-item'));

            // Проверка страницы редактирования курса
            $client->request('GET', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();
        }

        // Проверка страницы создания курса
        $client->request('GET', '/courses/new');
        $this->assertResponseOk();
    }

    // Проверка на 404
    public function urlProviderNotFound(): array
    {
        return [
            ['/courses/99999'],  // Курс с ID, которого нет
            ['/lessons/99999'],  // Урок с ID, которого нет
            ['/courses/99999'],
        ];
    }

    /**
     * @dataProvider urlProviderNotFound
     */
    public function testPageNotFound($url): void
    {
        $client = self::getClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }

    // Проверка корректных POST-запросов
    public function testPostActionsResponseOk(): void
    {
        $client = self::getClient();
        $entityManager = self::getEntityManager();
        $courses = $entityManager->getRepository(Course::class)->findAll();

        foreach ($courses as $course) {
            // Проверка редактирования курса (POST)
            $client->request('POST', '/courses/' . $course->getId() . '/edit', [
                'name' => 'Updated Name',
                'description' => 'Updated Description'
            ]);
            $this->assertResponseOk();
        }

        // Проверка создания нового курса (POST)
        $client->request('POST', '/courses/new', [
            'name' => 'Новый курс',
            'CharacterCode' => 'new-course',
            'description' => 'Описание нового курса'
        ]);
        $this->assertResponseOk();
    }


    // тест на успешное создание курса
    public function testCreateCourse(): void
    {
        // список курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');
        $this->assertResponseOk();

        // Нажимаем кнопку "Создать новый курс"
        $addCourse = $crawler->selectLink('Создать новый курс')->link();
        $crawler = $client->click($addCourse);
        $this->assertResponseOk();

        // заполняем форму на странице создания курса
        $form = $crawler->selectButton('Сохранить')->form([
            'course[characterCode]' => 'new-course',
            'course[name]' => 'Новый курс',
            'course[description]' => 'Описание нового курса'
        ]);
        $client->submit($form);

        // редирект
        $this->assertSame($client->getResponse()->headers->get('location'), '/courses');
        $client->followRedirect();
        $this->assertResponseOk();

        // Теперь получаем список курсов
        $crawler = $client->request('GET', '/courses');
        $this->assertResponseOk();

        // Находим последний добавленный курс, проверяя по названию и по описанию
        $lastCourse = $crawler->filter('.course-name')->last();
        $this->assertSame('Новый курс', $lastCourse->text());

        $courseDescription = $crawler->filter('.course-description')->last();
        $this->assertSame('Описание нового курса', $courseDescription->text());
    }


    // тест на ошибочное создание курса
    public function testCreateCourseError(): void
    {
        // список курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');
        $this->assertResponseOk();

        // Нажимаем кнопку "Создать новый курс"
        $addCourse = $crawler->selectLink('Создать новый курс')->link();
        $crawler = $client->click($addCourse);
        $this->assertResponseOk();

        // допускаем ошибку в characterCode
        $form = $crawler->selectButton('Сохранить')->form([
            'course[characterCode]' => 'ff',
            'course[name]' => 'Новый курс',
            'course[description]' => 'Описание нового курса',

        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        self::assertSelectorTextContains(
            'div',
            'Код курса должен содержать минимум 3 символа.'
        );


        // допускаем ошибку в name
        $form = $crawler->selectButton('Сохранить')->form([
            'course[characterCode]' => 'new-course',
            'course[name]' => 'aa',
            'course[description]' => 'Описание нового курса',

        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        self::assertSelectorTextContains(
            'div',
            'Название должно содержать минимум 3 символа.'
        );


        // допускаем ошибку в description
        $form = $crawler->selectButton('Сохранить')->form([
            'course[characterCode]' => 'new-course',
            'course[name]' => 'Новый курс',
            'course[description]' => 'ff',

        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        self::assertSelectorTextContains(
            'div',
            'Описание должно содержать минимум 3 символа.'
        );

    }


    // тест на успешное редактирование курса
    public function testEditCourse(): void
    {
        $entityManager = self::getEntityManager();

        // список курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses');
        $this->assertResponseOk();

        // переходим на первый курс
        $link = $crawler->filter('.course-item')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // получаем ID курса до редактирования
        $courseId = $client->getRequest()->attributes->get('id');


        // открываем страницу редактирования курса
        $editLink = $crawler->selectLink('Редактировать курс')->link();
        $crawler = $client->click($editLink);
        $this->assertResponseOk();


        // заполняем форму на странице редактирования курса и получаем id
        $form = $crawler->selectButton('Сохранить')->form([
            'course[characterCode]' => 'new-course',
            'course[name]' => 'Новый курс',
            'course[description]' => 'Описание нового курса'
        ]);
        $client->submit($form);

        // редирект
        $crawler = $client->followRedirect();
        self::assertRouteSame('app_course_show', ['id' => $courseId]);
        $this->assertResponseOk();

        // проверяем, что данные обновились
        $this->assertSame($crawler->filter('.text-center')->text(), 'Новый курс');

    }


    // тест на успешное удаление курса
    public function testDeleteCourse(): void
    {
        $client = self::getClient();
        $entityManager = self::getEntityManager();

        // список курсов
        $crawler = $client->request('GET', '/courses');
        $this->assertResponseOk();

        // сохраняем кол-во курсов до удаления
        $coursesCountBefore = count($entityManager->getRepository(Course::class)->findAll());

        // Находим и кликаем кнопку "Удалить" у первого курса
        $deleteForm = $crawler->filter('.delete-button')->first()->form();
        $client->submit($deleteForm);

        // Проверяем редирект после удаления
        self::assertResponseRedirects();
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        self::assertRouteSame('app_course_index');

        // проверяем что курс удален
        $coursesCountAfter = count($entityManager->getRepository(Course::class)->findAll());
        $this->assertSame($coursesCountAfter, $coursesCountBefore - 1);
    }

}
