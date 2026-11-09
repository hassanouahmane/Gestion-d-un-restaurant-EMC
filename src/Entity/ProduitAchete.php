<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ProduitAchete
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Ingredient $ingredient;

    #[ORM\Column(length: 150)]
    private string $nom;

    #[ORM\Column(length: 14, unique: true)]
    private string $codeBarres;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $allergenes;

    #[ORM\Column(length: 20)]
    private string $etat;
}
