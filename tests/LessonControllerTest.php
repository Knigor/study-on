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


    // Проверка корректных Статусов GET
    public function testGetActionsResponseOk(): void
    {
        //проверка страниц всех курсов
        $client = self::getClient();
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson){
            // страница урока
            $client->request('GET', '/lessons/' . $lesson->getId());
            $this->assertResponseOk();

            // страница создания нового урока
            $client->request('GET', '/lessons/new/' . $lesson->getCourse()->getId());
            $this->assertResponseOk();

            // страница редактирования курса
            $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
        }

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

        // Нажимаем кнопку "Добавить урок" (ссылка на страницу создания урока)
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

        // редирект
        $crawler = $client->followRedirect();
        self::assertRouteSame('app_course_show', ['id' => $course->getId()]);
        $this->assertResponseOk();

        // сравнение кол-во курсов
        // $client->followRedirect();
        // $this->assertCount($lessonCountBefore + 1, $client->getCrawler()->filter('.lesson-item'));
    }


}