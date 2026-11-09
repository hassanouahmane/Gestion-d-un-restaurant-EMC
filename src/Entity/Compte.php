<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class Compte implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(length: 80)]
    private string $identifiant;

    #[ORM\Column(length: 24)]
    private string $role;

    #[ORM\Column(length: 255)]
    private string $password;

    public function __construct(string $identifiant, string $role, string $password)
    {
        $this->identifiant = $identifiant;
        $this->role = $role;
        $this->password = $password;
    }

    public function getUserIdentifier(): string
    {
        return $this->identifiant;
    }

    public function getRoles(): array
    {
        return [$this->role];
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }
}
