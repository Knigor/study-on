<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class LessonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nameLesson', TextType::class, [
                'label' => 'Название урока',
                'attr' => [
                    'placeholder' => 'Введите название урока...',
                ],
            ])
            ->add('lessonContent', TextareaType::class, [
                'label' => 'Содержание урока',
                'attr' => [
                    'placeholder' => 'Введите содержание урока...',
                    'rows' => 5,
                ],
            ])
            ->add('orderNumber', IntegerType::class, [
                'label' => 'Цена',
                'attr' => [
                    'placeholder' => 'Введите цену урока...',
                ],
            ])
            ->add('course', HiddenType::class, [
                'mapped' => false,
            ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
