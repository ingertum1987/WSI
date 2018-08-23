<?php

namespace App\Form\Transaction;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UploadCsvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', TextType::class, [
                'label' => 'Выберите период',
            ])
            ->add('file', FileType::class, [
                'label' => 'Добавить файл',
                'multiple' => false,
            ])
        ;
    }
}

