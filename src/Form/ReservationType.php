<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // tylko pola gościa — status i gra ustawia serwer (ochrona przed mass assignment)
        $builder
            ->add('guestName', TextType::class, [
                'label' => 'Imię i nazwisko',
            ])
            ->add('guestPhone', TextType::class, [
                'label' => 'Telefon',
            ])
            ->add('guestEmail', EmailType::class, [
                'label' => 'E-mail (opcjonalnie)',
                'required' => false,
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Od',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Do',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
