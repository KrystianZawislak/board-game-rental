<?php

namespace App\Entity;

use App\Enum\GameCategory;
use App\Enum\ReservationStatus;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Podaj tytuł gry.')]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Podaj opis gry.')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Podaj minimalną liczbę graczy.')]
    #[Assert\Positive(message: 'Liczba graczy musi być dodatnia.')]
    private ?int $minPlayers = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Podaj maksymalną liczbę graczy.')]
    #[Assert\Positive(message: 'Liczba graczy musi być dodatnia.')]
    #[Assert\GreaterThanOrEqual(propertyPath: 'minPlayers', message: 'Maks. graczy nie może być mniejsze niż min.')]
    private ?int $maxPlayers = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Podaj czas gry.')]
    #[Assert\Positive(message: 'Czas gry musi być dodatni.')]
    private ?int $playingTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(enumType: GameCategory::class)]
    #[Assert\NotNull(message: 'Wybierz kategorię.')]
    private ?GameCategory $category = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'game', cascade: ['remove'])]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMinPlayers(): ?int
    {
        return $this->minPlayers;
    }

    public function setMinPlayers(int $minPlayers): static
    {
        $this->minPlayers = $minPlayers;

        return $this;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): static
    {
        $this->maxPlayers = $maxPlayers;

        return $this;
    }

    public function getPlayingTime(): ?int
    {
        return $this->playingTime;
    }

    public function setPlayingTime(int $playingTime): static
    {
        $this->playingTime = $playingTime;

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getCategory(): ?GameCategory
    {
        return $this->category;
    }

    public function setCategory(GameCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setGame($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getGame() === $this) {
                $reservation->setGame(null);
            }
        }

        return $this;
    }

    /**
     * Czy gra jest wolna DZIŚ (model kalendarzowy — rezerwacja w przyszłości nie blokuje dnia dzisiejszego):
     *  - wydana (ISSUED) i niezwrócona = nie ma jej teraz,
     *  - zgłoszona/potwierdzona, której okres obejmuje dzisiejszą datę = zajęta dziś.
     */
    public function isCurrentlyAvailable(): bool
    {
        $today = new \DateTimeImmutable('today');

        foreach ($this->reservations as $reservation) {
            $status = $reservation->getStatus();

            if ($status === ReservationStatus::ISSUED) {
                return false;
            }

            if (($status === ReservationStatus::PENDING || $status === ReservationStatus::CONFIRMED)
                && $reservation->getStartDate() <= $today
                && $reservation->getEndDate() >= $today
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Aktywne rezerwacje, których termin jeszcze się nie skończył — do pokazania
     * listy zajętych terminów na stronie gry (bez danych osobowych). Posortowane od najbliższej.
     *
     * @return Reservation[]
     */
    public function getUpcomingReservations(): array
    {
        $today = new \DateTimeImmutable('today');

        $upcoming = array_filter(
            $this->reservations->toArray(),
            static fn (Reservation $r): bool => $r->getStatus() !== ReservationStatus::RETURNED
                && $r->getEndDate() >= $today,
        );

        usort($upcoming, static fn (Reservation $a, Reservation $b): int => $a->getStartDate() <=> $b->getStartDate());

        return $upcoming;
    }

    /**
     * Czy gra ma aktywną rezerwację (oczekującą, potwierdzoną lub wydaną).
     * Zwrócone (RETURNED) to już zamknięta historia — nie liczą się.
     */
    public function hasActiveReservations(): bool
    {
        foreach ($this->reservations as $reservation) {
            if ($reservation->getStatus() !== ReservationStatus::RETURNED) {
                return true;
            }
        }

        return false;
    }
}
