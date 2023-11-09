<?php

namespace App\Entity;

use App\Repository\AnimeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnimeRepository::class)]
class Anime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private ?int $idApi = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'animes')]
    private Collection $users;

    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: UserRegarderAnime::class, orphanRemoval: true)]
    private Collection $userRegarderAnimes;

    #[ORM\OneToOne(inversedBy: 'anime', cascade: ['persist', 'remove'])]
    private ?Discussion $discussion = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->userRegarderAnimes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdApi(): ?int
    {
        return $this->idApi;
    }

    public function setIdApi(int $idApi): static
    {
        $this->idApi = $idApi;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addAnime($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeAnime($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, UserRegarderAnime>
     */
    public function getUserRegarderAnimes(): Collection
    {
        return $this->userRegarderAnimes;
    }

    public function addUserRegarderAnime(UserRegarderAnime $userRegarderAnime): static
    {
        if (!$this->userRegarderAnimes->contains($userRegarderAnime)) {
            $this->userRegarderAnimes->add($userRegarderAnime);
            $userRegarderAnime->setAnime($this);
        }

        return $this;
    }

    public function removeUserRegarderAnime(UserRegarderAnime $userRegarderAnime): static
    {
        if ($this->userRegarderAnimes->removeElement($userRegarderAnime)) {
            // set the owning side to null (unless already changed)
            if ($userRegarderAnime->getAnime() === $this) {
                $userRegarderAnime->setAnime(null);
            }
        }

        return $this;
    }

    public function getDiscussion(): ?Discussion
    {
        return $this->discussion;
    }

    public function setDiscussion(?Discussion $discussion): static
    {
        $this->discussion = $discussion;

        return $this;
    }
}
