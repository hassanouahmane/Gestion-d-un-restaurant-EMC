<?php

namespace App\Controller\Admin;

use App\Entity\Preparation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

final class PreparationCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return Preparation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Préparation')->setEntityLabelInPlural('Préparations');
    }

    public function configureFields(string $pageName): iterable
    {
        yield $this->texte('nom', 'Nom');
    }
}
