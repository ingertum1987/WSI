<?php

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Тема',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Введите текст',
                'attr' => [
                    'rows'=>15
                ]
            ])
            ->add('file', FileType::class, [
                'label' => 'Добавить вложение',
                'multiple' => true,
                'attr'     => [
                    'multiple' => 'multiple'
                ],
                'required' => false
            ])
        ;
    }
}