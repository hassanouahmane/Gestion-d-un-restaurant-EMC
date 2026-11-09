<?php

namespace App\Controller\Admin;

use App\Entity\DoseIngredient;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

final class DoseIngredientCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return DoseIngredient::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Dose d’ingrédient')->setEntityLabelInPlural('Doses d’ingrédients');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('recette', 'Recette');
        yield AssociationField::new('ingredient', 'Ingrédient');
        yield $this->decimal('quantite', 'Quantité');
        yield $this->unite('unite', 'Unité');
    }
}
