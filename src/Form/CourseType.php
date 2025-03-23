<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('characterCode', TextType::class, [
                'label' => 'Код курса',
                'attr' => [
                    'placeholder' => 'Введите код курса...',
                ],

            ])
            ->add('name', TextType::class, [
                'label' => 'Название курса',
                'attr' => [
                    'placeholder' => 'Введите название...',
                ],

            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание курса',
                'attr' => [
                    'placeholder' => 'Введите описание курса...',
                    'rows' => 5,
                ],

            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
