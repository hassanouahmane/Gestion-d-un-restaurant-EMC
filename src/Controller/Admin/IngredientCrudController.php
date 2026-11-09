<?php

namespace App\Controller\Admin;

use App\Entity\Ingredient;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

final class IngredientCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return Ingredient::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Ingrédient')->setEntityLabelInPlural('Ingrédients');
    }

    public function configureFields(string $pageName): iterable
    {
        yield $this->texte('nom', 'Nom');
        yield ChoiceField::new('uniteStock', 'Unité de stock')->setChoices(['Gramme' => 'g', 'Millilitre' => 'ml', 'Pièce' => 'piece'])->setFormTypeOption('disabled', $pageName === Crud::PAGE_EDIT);
    }
}
