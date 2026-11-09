<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TableSalle::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?TableSalle $tableSalle = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateCommande = null;

    #[ORM\Column(length: 24, options: ['default' => 'brouillon'])]
    private string $statut = 'brouillon';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTableSalle(): ?TableSalle
    {
        return $this->tableSalle;
    }

    public function setTableSalle(TableSalle $tableSalle): static
    {
        $this->tableSalle = $tableSalle;

        return $this;
    }

    public function getDateCommande(): ?\DateTimeImmutable
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeImmutable $dateCommande): static
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
