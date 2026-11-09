<?php

namespace App\Controller\Admin;

use App\Entity\Fournisseur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

final class FournisseurCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return Fournisseur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Fournisseur')->setEntityLabelInPlural('Fournisseurs');
    }

    public function configureFields(string $pageName): iterable
    {
        yield $this->texte('nom', 'Nom');
    }
}
