<?php

namespace App\Entity;

use App\Service\Unites;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[UniqueEntity(fields: ['recette', 'ingredient'], ignoreNull: true, message: 'Cette association existe déjà.')]
#[ORM\UniqueConstraint(columns: ['recette_id', 'ingredient_id'])]
class DoseIngredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Recette::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recette $recette = null;

    #[ORM\ManyToOne(targetEntity: Ingredient::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ingredient $ingredient = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 4)]
    private ?string $quantite = null;

    #[ORM\Column(length: 16)]
    private ?string $unite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecette(): ?Recette
    {
        return $this->recette;
    }

    public function setRecette(Recette $recette): static
    {
        $this->recette = $recette;

        return $this;
    }

    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }

    public function setIngredient(Ingredient $ingredient): static
    {
        $this->ingredient = $ingredient;

        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(string $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): static
    {
        $this->unite = $unite;

        return $this;
    }

    #[Assert\Callback]
    public function validerUnite(ExecutionContextInterface $contexte): void
    {
        $uniteStock = $this->ingredient?->getUniteStock();
        if ($this->unite === null || $uniteStock === null) {
            return;
        }
        try {
            Unites::convertir(1, $this->unite, $uniteStock);
        } catch (\DomainException $exception) {
            $contexte->buildViolation($exception->getMessage())->atPath('unite')->addViolation();
        }
    }
}
