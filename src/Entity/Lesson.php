<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(length: 255)]
    private ?string $nameLesson = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $lessonContent = null;

    #[ORM\Column(nullable: true)]
    private ?int $orderNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getNameLesson(): ?string
    {
        return $this->nameLesson;
    }

    public function setNameLesson(string $nameLesson): static
    {
        $this->nameLesson = $nameLesson;

        return $this;
    }

    public function getLessonContent(): ?string
    {
        return $this->lessonContent;
    }

    public function setLessonContent(string $lessonContent): static
    {
        $this->lessonContent = $lessonContent;

        return $this;
    }

    public function getOrderNumber(): ?int
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?int $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }
}
