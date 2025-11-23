<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column]
    private string $name;
    #[ORM\Column]
    private string $email;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailVerifiedAt = null;
    #[ORM\Column]
    private string $password;
    #[ORM\Column(nullable: true)]
    private ?string $rememberToken = null;
    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $currentTeamId = null;
    #[ORM\Column(nullable: true)]
    private ?string $profilePhotoPath = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $twoFactorSecret = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $twoFactorRecoveryCodes = null;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $twoFactorConfirmedAt = null;

    #[ORM\OneToOne(targetEntity: Pessoas::class, mappedBy: 'user')]
    private ?Pessoas $pessoa = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeInterface
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeInterface $emailVerifiedAt): self
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRememberToken(): ?string
    {
        return $this->rememberToken;
    }

    public function setRememberToken(?string $rememberToken): self
    {
        $this->rememberToken = $rememberToken;
        return $this;
    }

    public function getCurrentTeamId(): ?int
    {
        return $this->currentTeamId;
    }

    public function setCurrentTeamId(?int $currentTeamId): self
    {
        $this->currentTeamId = $currentTeamId;
        return $this;
    }

    public function getProfilePhotoPath(): ?string
    {
        return $this->profilePhotoPath;
    }

    public function setProfilePhotoPath(?string $profilePhotoPath): self
    {
        $this->profilePhotoPath = $profilePhotoPath;
        return $this;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function setTwoFactorSecret(?string $twoFactorSecret): self
    {
        $this->twoFactorSecret = $twoFactorSecret;
        return $this;
    }

    public function getTwoFactorRecoveryCodes(): ?string
    {
        return $this->twoFactorRecoveryCodes;
    }

    public function setTwoFactorRecoveryCodes(?string $twoFactorRecoveryCodes): self
    {
        $this->twoFactorRecoveryCodes = $twoFactorRecoveryCodes;
        return $this;
    }

    public function getTwoFactorConfirmedAt(): ?\DateTimeInterface
    {
        return $this->twoFactorConfirmedAt;
    }

    public function setTwoFactorConfirmedAt(?\DateTimeInterface $twoFactorConfirmedAt): self
    {
        $this->twoFactorConfirmedAt = $twoFactorConfirmedAt;
        return $this;
    }

    public function getPessoa(): ?Pessoas
    {
        return $this->pessoa;
    }

    public function setPessoa(?Pessoas $pessoa): self
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        // Garantir que todo usuário tenha pelo menos ROLE_USER
        $roles = ['ROLE_USER'];
        
        // Se houver campo roles na entity, usar ele:
        // $roles = json_decode($this->roles ?? '[]', true);
        // $roles[] = 'ROLE_USER';
        
        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Se você armazenar dados temporários sensíveis na entity do usuário, limpe-os aqui
        // $this->plainPassword = null;
    }

}
