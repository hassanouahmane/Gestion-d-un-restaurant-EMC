<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['ligne_commande_id', 'groupe_choix_id'])]
class PlatChoisi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LigneCommande::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?LigneCommande $ligneCommande = null;

    #[ORM\ManyToOne(targetEntity: GroupeChoix::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?GroupeChoix $groupeChoix = null;

    #[ORM\ManyToOne(targetEntity: Plat::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plat $plat = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLigneCommande(): ?LigneCommande
    {
        return $this->ligneCommande;
    }

    public function setLigneCommande(LigneCommande $ligneCommande): static
    {
        $this->ligneCommande = $ligneCommande;

        return $this;
    }

    public function getGroupeChoix(): ?GroupeChoix
    {
        return $this->groupeChoix;
    }

    public function setGroupeChoix(GroupeChoix $groupeChoix): static
    {
        $this->groupeChoix = $groupeChoix;

        return $this;
    }

    public function getPlat(): ?Plat
    {
        return $this->plat;
    }

    public function setPlat(Plat $plat): static
    {
        $this->plat = $plat;

        return $this;
    }
}
