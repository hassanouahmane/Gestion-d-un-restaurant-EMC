<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\Column(length: 16)]
    private ?string $uniteStock = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getUniteStock(): ?string
    {
        return $this->uniteStock;
    }

    public function setUniteStock(string $uniteStock): static
    {
        $this->uniteStock = $uniteStock;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Nouvel ingrédient';
    }
}
