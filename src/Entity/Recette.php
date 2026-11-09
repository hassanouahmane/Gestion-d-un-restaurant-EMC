<?php

namespace App\Entity;

use App\Service\Unites;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[UniqueEntity(fields: 'plat', ignoreNull: true, message: 'Cette association existe déjà.')]
#[UniqueEntity(fields: 'preparation', ignoreNull: true, message: 'Cette association existe déjà.')]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 4)]
    private ?string $rendement = null;

    #[ORM\Column(length: 16)]
    private ?string $uniteRendement = null;

    #[ORM\OneToOne(targetEntity: Plat::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Plat $plat = null;

    #[ORM\OneToOne(targetEntity: Preparation::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Preparation $preparation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRendement(): ?string
    {
        return $this->rendement;
    }

    public function setRendement(string $rendement): static
    {
        $this->rendement = $rendement;

        return $this;
    }

    public function getUniteRendement(): ?string
    {
        return $this->uniteRendement;
    }

    public function setUniteRendement(string $uniteRendement): static
    {
        $this->uniteRendement = $uniteRendement;

        return $this;
    }

    public function getPlat(): ?Plat
    {
        return $this->plat;
    }

    public function setPlat(?Plat $plat): static
    {
        $this->plat = $plat;

        return $this;
    }

    public function getPreparation(): ?Preparation
    {
        return $this->preparation;
    }

    public function setPreparation(?Preparation $preparation): static
    {
        $this->preparation = $preparation;

        return $this;
    }

    #[Assert\Callback]
    public function valider(ExecutionContextInterface $contexte): void
    {
        if (($this->plat === null) === ($this->preparation === null)) {
            $contexte->buildViolation('Sélectionne un plat ou une préparation.')->atPath('plat')->addViolation();
        }
        if ($this->plat !== null && $this->uniteRendement !== null) {
            try {
                Unites::convertir(1, $this->uniteRendement, 'portion');
            } catch (\DomainException) {
                $contexte->buildViolation('Le rendement d’un plat est exprimé en portions.')->atPath('uniteRendement')->addViolation();
            }
        }
    }

    public function __toString(): string
    {
        return $this->plat?->getNom() ?? $this->preparation?->getNom() ?? 'Nouvelle recette';
    }
}
