<?php

namespace App\Controller\Admin;

use App\Entity\DosePreparation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

final class DosePreparationCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return DosePreparation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Dose de préparation')->setEntityLabelInPlural('Doses de préparation');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('recette', 'Recette de plat');
        yield AssociationField::new('preparation', 'Préparation utilisée');
        yield $this->decimal('quantite', 'Quantité');
        yield $this->unite('unite', 'Unité');
    }
}
