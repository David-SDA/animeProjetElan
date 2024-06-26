<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 40, unique: true)]
    #[Assert\Length(
        min: 4,
        max: 40,
        minMessage: 'Your username must be at least 4 characters long',
        maxMessage: 'Your username cannot be longer than 40 characters long',
    )]
    private ?string $username = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options:["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $registrationDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options:["default" => "true"])]
    private ?bool $visible = null;

    #[ORM\Column(options:["default" => "false"])]
    private ?bool $banned = null;

    #[ORM\ManyToMany(targetEntity: Personnage::class, inversedBy: 'users')]
    private Collection $personnages;

    #[ORM\ManyToMany(targetEntity: Anime::class, inversedBy: 'users')]
    private Collection $animes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Evenement::class, orphanRemoval: true)]
    private Collection $evenements;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Discussion::class)]
    private Collection $discussions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Post::class)]
    private Collection $posts;

    #[ORM\ManyToMany(targetEntity: Post::class, inversedBy: 'users')]
    private Collection $likePosts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserRegarderAnime::class, orphanRemoval: true)]
    private Collection $userRegarderAnimes;

    #[ORM\Column(type: 'boolean')]
    private $isVerified = false;

    #[ORM\Column(nullable: true)]
    private ?bool $darkMode = null;

    public function __construct()
    {
        $this->registrationDate = new DateTime();
        $this->visible = true;
        $this->banned = false;
        $this->personnages = new ArrayCollection();
        $this->animes = new ArrayCollection();
        $this->evenements = new ArrayCollection();
        $this->discussions = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->likePosts = new ArrayCollection();
        $this->userRegarderAnimes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeInterface $registrationDate): static
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function isBanned(): ?bool
    {
        return $this->banned;
    }

    public function setBanned(bool $banned): static
    {
        $this->banned = $banned;

        return $this;
    }

    /**
     * @return Collection<int, Personnage>
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function addPersonnage(Personnage $personnage): static
    {
        if (!$this->personnages->contains($personnage)) {
            $this->personnages->add($personnage);
        }

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    /**
     * @return Collection<int, Anime>
     */
    public function getAnimes(): Collection
    {
        return $this->animes;
    }

    public function addAnime(Anime $anime): static
    {
        if (!$this->animes->contains($anime)) {
            $this->animes->add($anime);
        }

        return $this;
    }

    public function removeAnime(Anime $anime): static
    {
        $this->animes->removeElement($anime);

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): static
    {
        if (!$this->evenements->contains($evenement)) {
            $this->evenements->add($evenement);
            $evenement->setUser($this);
        }

        return $this;
    }

    public function removeEvenement(Evenement $evenement): static
    {
        if ($this->evenements->removeElement($evenement)) {
            // set the owning side to null (unless already changed)
            if ($evenement->getUser() === $this) {
                $evenement->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Discussion>
     */
    public function getDiscussions(): Collection
    {
        return $this->discussions;
    }

    public function addDiscussion(Discussion $discussion): static
    {
        if (!$this->discussions->contains($discussion)) {
            $this->discussions->add($discussion);
            $discussion->setUser($this);
        }

        return $this;
    }

    public function removeDiscussion(Discussion $discussion): static
    {
        if ($this->discussions->removeElement($discussion)) {
            // set the owning side to null (unless already changed)
            if ($discussion->getUser() === $this) {
                $discussion->setUser(null);
            }
        }

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
            $post->setUser($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getUser() === $this) {
                $post->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getLikePosts(): Collection
    {
        return $this->likePosts;
    }

    public function addLikePost(Post $likePost): static
    {
        if (!$this->likePosts->contains($likePost)) {
            $this->likePosts->add($likePost);
        }

        return $this;
    }

    public function removeLikePost(Post $likePost): static
    {
        $this->likePosts->removeElement($likePost);

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
            $userRegarderAnime->setUser($this);
        }

        return $this;
    }

    public function removeUserRegarderAnime(UserRegarderAnime $userRegarderAnime): static
    {
        if ($this->userRegarderAnimes->removeElement($userRegarderAnime)) {
            // set the owning side to null (unless already changed)
            if ($userRegarderAnime->getUser() === $this) {
                $userRegarderAnime->setUser(null);
            }
        }

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isDarkMode(): ?bool
    {
        return $this->darkMode;
    }

    public function setDarkMode(?bool $darkMode): static
    {
        $this->darkMode = $darkMode;

        return $this;
    }
}
