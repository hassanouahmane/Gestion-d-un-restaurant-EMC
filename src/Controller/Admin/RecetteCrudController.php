<?php

namespace App\Controller\Admin;

use App\Entity\Recette;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

final class RecetteCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return Recette::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Recette')->setEntityLabelInPlural('Recettes');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('plat', 'Plat')->setRequired(false);
        yield AssociationField::new('preparation', 'Préparation')->setRequired(false);
        yield $this->decimal('rendement', 'Rendement');
        yield $this->unite('uniteRendement', 'Unité de rendement');
    }
}
