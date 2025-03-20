<?php

namespace App\DataFixtures;

use App\Entity\Lesson;
use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $courses = [
            [
                'name' => 'Разработка программного обеспечения на базе Windows',
                'CharacterCode' => 'windows-development',
                'description' => 'Данный курс предназначен для людей, которые хотят научиться веб разработке на windows'
            ],
            [
                'name' => 'Разработка программного обеспечения на базе MacOS',
                'CharacterCode' => 'macos-development',
                'description' => 'Данный курс предназначен для людей, которые хотят научиться веб разработке на macos'
            ],
            [
                'name' => 'Разработка программного обеспечения на базе Android',
                'CharacterCode' => 'android-development',
                'description' => 'Данный курс предназначен для людей, которые хотят научиться веб разработке на android'
            ],
            [
                'name' => 'Разработка программного обеспечения на базе IOS',
                'CharacterCode' => 'ios-development',
                'description' => 'Данный курс предназначен для людей, которые хотят научиться веб разработке на ios'
            ],
            [
                'name' => 'Разработка программного обеспечения на базе Linux',
                'CharacterCode' => 'linux-development',
                'description' => 'Данный курс предназначен для людей, которые хотят научиться веб разработке на linux'
            ]
        ];

        foreach ($courses as $data) {
            $course = new Course();

            $course->setName($data['name']);
            $course->setCharacterCode($data['CharacterCode']);
            $course->setDescription($data['description']);

            for ($i = 1; $i <= 5; $i++) {
                $lesson = new Lesson();
                $lesson->setNameLesson('Урок ' . $i);
                $lesson->setLessonContent("Контент урока $i для курса {$data['name']}.");
                $lesson->setOrderNumber($i);
                $lesson->setCourse($course);

                $manager->persist($lesson);
            }

            $manager->persist($course);
        }


        $manager->flush();
    }
}
