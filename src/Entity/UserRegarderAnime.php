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
    private ?string $status = null;

    #[ORM\Column]
    private ?int $nbEpisodesWatched = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startedWatching = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endedWatching = null;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNbEpisodesWatched(): ?int
    {
        return $this->nbEpisodesWatched;
    }

    public function setNbEpisodesWatched(int $nbEpisodesWatched): static
    {
        $this->nbEpisodesWatched = $nbEpisodesWatched;

        return $this;
    }

    public function getStartedWatching(): ?\DateTimeInterface
    {
        return $this->startedWatching;
    }

    public function setStartedWatching(?\DateTimeInterface $startedWatching): static
    {
        $this->startedWatching = $startedWatching;

        return $this;
    }

    public function getEndedWatching(): ?\DateTimeInterface
    {
        return $this->endedWatching;
    }

    public function setEndedWatching(?\DateTimeInterface $endedWatching): static
    {
        $this->endedWatching = $endedWatching;

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
