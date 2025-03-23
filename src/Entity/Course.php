<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[UniqueEntity('characterCode')]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Код курса должен содержать минимум 3 символа.",
        maxMessage: "Код курса должен содержать максимум 255 символов."
    )]
    private ?string $characterCode = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Название должно содержать минимум 3 символа.",
        maxMessage: "Название должно содержать максимум 255 символов."
    )]
    private ?string $name = null;

    #[ORM\Column(length: 1000, nullable: true)]
    #[Assert\Length(
        min: 3,
        max: 1000,
        minMessage: "Описание должно содержать минимум 3 символа.",
        maxMessage: "Описание должно содержать максимум 255 символов."
    )]
    private ?string $description = null;

    /**
     * @var Collection<int, Lesson>
     */
    #[ORM\OneToMany(targetEntity: Lesson::class, mappedBy: 'course', cascade: ['remove'])]
    private Collection $lessons;

    public function __construct()
    {
        $this->lessons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCharacterCode(): ?string
    {
        return $this->characterCode;
    }

    public function setCharacterCode(string $characterCode): static
    {
        $this->characterCode = $characterCode;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): static
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setCourse($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): static
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getCourse() === $this) {
                $lesson->setCourse(null);
            }
        }

        return $this;
    }
}
