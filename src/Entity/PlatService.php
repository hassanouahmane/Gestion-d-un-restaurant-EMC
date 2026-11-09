<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PlatService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private LigneCommande $ligneCommande;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Plat $plat;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $quantite;

    #[ORM\Column(length: 24)]
    private string $statut;

    #[ORM\Column(type: Types::JSON)]
    private array $besoins;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $prixVenteUnitaire;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $coutUnitaire;
}
