<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Inventaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Ingredient $ingredient;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateComptage;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 4)]
    private string $stockTheorique;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 4)]
    private string $stockCompte;
}
