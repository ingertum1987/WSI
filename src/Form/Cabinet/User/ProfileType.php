<?php

namespace App\Form\Cabinet\User;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.profile.name',
                'attr' => [
                    'class' => 'form-control form-control-line',
                    'placeholder' => 'placeholder.profile.name'
                ],
            ])
            ->add('surname', TextType::class, [
                'label' => 'form.profile.surname',
                'attr' => [
                    'class' => 'form-control form-control-line',
                    'placeholder' => 'placeholder.profile.surname'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control form-control-line',
                    'placeholder' => 'myemail@yandex.ru'
                ]
            ])
            ->add('username', TextType::class, [
                'label' => 'form.profile.username',
                'attr' => [
                    'class' => 'form-control form-control-line',
                    'placeholder' => 'placeholder.profile.username'
                ]
            ])
            ->add('bitcoinWallet', TextType::class, [
                'label' => 'form.profile.bitcoinWallet',
                'attr' => [
                    'class' => 'form-control form-control-line',
                    'placeholder' => '1KoX6AA5VTdbBTkw27YEqKfAtTeQo97RRt'
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'form.profile.password',
                'attr' => [
                    'class' => 'form-control form-control-line',
                    'placeholder' => 'placeholder.profile.password'
                ],
                'required' => false
            ])
            ->add('showInvestor', CheckboxType::class, [
                'label' => 'form.profile.showInvestor',
                'attr' => [
                    'class' => 'custom-control-input'
                ],
                'required' => false,
            ])
            ->add('username', TextType::class, [
                'label' => 'form.profile.username',
                'attr' => [
                    'class' => 'form-control form-control-line',
                    'placeholder' => 'placeholder.profile.username'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'translation_domain' => 'form',
                'data_class' => User::class,
            ]);
    }
}