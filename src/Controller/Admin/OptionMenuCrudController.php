<?php

namespace App\Controller\Admin;

use App\Entity\OptionMenu;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

final class OptionMenuCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return OptionMenu::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Option de menu')->setEntityLabelInPlural('Options de menu');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('groupeChoix', 'Groupe de choix');
        yield AssociationField::new('plat', 'Plat autorisé');
    }
}
