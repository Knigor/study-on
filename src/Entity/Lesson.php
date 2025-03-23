<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
    #[Assert\NotBlank(message: "Название урока не может быть пустым.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Название урока должно содержать минимум 3 символа.",
        maxMessage: "Название урока должно содержать максимум 255 символов."
    )]
    private ?string $nameLesson = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Описание не может быть пустым.")]
    #[Assert\Length(
        min: 3,
        max: 1000,
        minMessage: "Описание должно содержать минимум 3 символа.",
        maxMessage: "Описание должно содержать максимум 255 символов."
    )]
    private ?string $lessonContent = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "Цена урока должна быть положительным числом.")]
    #[Assert\Range(notInRangeMessage: 'Цена курса не должна превышать {{ max }} рублей.', max: 10000,)]
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
