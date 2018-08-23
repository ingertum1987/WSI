<?php

namespace App\Form\Cabinet\User;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.document.name',
                'attr' => [
                    'class' => 'form-control form-control-line',
                    'placeholder' => 'placeholder.document.name'
                ]
            ])
            ->add('img', FileType::class, [
                'label' => 'form.document.img',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('file', FileType::class, [
                'label' => 'form.document.file',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'form.save',
                'attr' => [
                    'class' => 'btn btn-danger waves-effect waves-light'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'translation_domain' => 'form',
                'data_class' => Document::class,
            ]);
    }
}