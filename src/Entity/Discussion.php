<?php

namespace App\Entity;

use App\Repository\DiscussionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiscussionRepository::class)]
class Discussion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options:["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(options:["default" => "false"])]
    private ?bool $estVerrouiller = null;

    #[ORM\ManyToOne(inversedBy: 'discussions')]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'discussion', targetEntity: Post::class, orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToOne(mappedBy: 'discussion', cascade: ['persist', 'remove'])]
    private ?Anime $anime = null;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function isEstVerrouiller(): ?bool
    {
        return $this->estVerrouiller;
    }

    public function setEstVerrouiller(bool $estVerrouiller): static
    {
        $this->estVerrouiller = $estVerrouiller;

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

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setDiscussion($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getDiscussion() === $this) {
                $post->setDiscussion(null);
            }
        }

        return $this;
    }

    public function getAnime(): ?Anime
    {
        return $this->anime;
    }

    public function setAnime(?Anime $anime): static
    {
        // unset the owning side of the relation if necessary
        if ($anime === null && $this->anime !== null) {
            $this->anime->setDiscussion(null);
        }

        // set the owning side of the relation if necessary
        if ($anime !== null && $anime->getDiscussion() !== $this) {
            $anime->setDiscussion($this);
        }

        $this->anime = $anime;

        return $this;
    }
}
