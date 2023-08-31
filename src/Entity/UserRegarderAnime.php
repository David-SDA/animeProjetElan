<?php

namespace App\Entity;

use App\Repository\UserRegarderAnimeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRegarderAnimeRepository::class)]
class UserRegarderAnime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $etat = null;

    #[ORM\Column]
    private ?int $nombreEpisodeVu = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebutVisionnage = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinVisionnage = null;

    #[ORM\ManyToOne(inversedBy: 'userRegarderAnimes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userRegarderAnimes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Anime $anime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getNombreEpisodeVu(): ?int
    {
        return $this->nombreEpisodeVu;
    }

    public function setNombreEpisodeVu(int $nombreEpisodeVu): static
    {
        $this->nombreEpisodeVu = $nombreEpisodeVu;

        return $this;
    }

    public function getDateDebutVisionnage(): ?\DateTimeInterface
    {
        return $this->dateDebutVisionnage;
    }

    public function setDateDebutVisionnage(?\DateTimeInterface $dateDebutVisionnage): static
    {
        $this->dateDebutVisionnage = $dateDebutVisionnage;

        return $this;
    }

    public function getDateFinVisionnage(): ?\DateTimeInterface
    {
        return $this->dateFinVisionnage;
    }

    public function setDateFinVisionnage(?\DateTimeInterface $dateFinVisionnage): static
    {
        $this->dateFinVisionnage = $dateFinVisionnage;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAnime(): ?Anime
    {
        return $this->anime;
    }

    public function setAnime(?Anime $anime): static
    {
        $this->anime = $anime;

        return $this;
    }
}
