<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[UniqueEntity(fields: ['groupeChoix', 'plat'], ignoreNull: true, message: 'Cette association existe déjà.')]
#[ORM\UniqueConstraint(columns: ['groupe_choix_id', 'plat_id'])]
class OptionMenu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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
