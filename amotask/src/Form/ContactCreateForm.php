<?php

namespace App\Form;

use App\Dto\ContactCreateDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactCreateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Имя',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Фамилия',
                'required' => true,
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Возраст',
                'required' => true,
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Пол',
                'choices' => [
                    'Мужской' => 'Мужской',
                    'Женский' => 'Женский'
                ],
                'required' => true,
            ])
            ->add('phone', TelType::class, [
                'label' => 'Телефон',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ContactCreateDTO::class,
            'csrf_protection' => false,
        ]);
    }
}