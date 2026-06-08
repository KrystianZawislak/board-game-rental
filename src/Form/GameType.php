<?php

namespace App\Form;

use App\Entity\Game;
use App\Enum\GameCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Tytuł'])
            ->add('category', EnumType::class, [
                'label' => 'Kategoria',
                'class' => GameCategory::class,
                'choice_label' => static fn (GameCategory $c): string => $c->label(),
            ])
            ->add('minPlayers', IntegerType::class, ['label' => 'Min. graczy'])
            ->add('maxPlayers', IntegerType::class, ['label' => 'Maks. graczy'])
            ->add('playingTime', IntegerType::class, ['label' => 'Czas gry (min)'])
            ->add('description', TextareaType::class, ['label' => 'Opis']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Game::class]);
    }
}
