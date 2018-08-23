<?php

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PartnerSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('get')
            ->add('id', TextType::class, [
                'label' => 'ID инвестора',
                'required' => false
            ])
            ->add('name', TextType::class, [
                'label' => 'Имя',
                'required' => false
            ])
            ->add('surname', TextType::class, [
                'label' => 'Фамилия',
                'required' => false
            ])
            ->add('createdAt', TextType::class, [
                'label' => 'Дата инвестирования',
                'required' => false
            ])
            ->add('sum', TextType::class, [
                'label' => 'Сумма инвестирования',
                'required' => false
            ])
            ->add('income', TextType::class, [
                'label' => 'Доход',
                'required' => false
            ])
            ->add('refincome', TextType::class, [
                'label' => 'Реферальный доход',
                'required' => false
            ])
        ;
    }
}