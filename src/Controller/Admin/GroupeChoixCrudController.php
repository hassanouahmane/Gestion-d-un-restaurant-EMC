<?php

namespace App\Controller\Admin;

use App\Entity\GroupeChoix;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

final class GroupeChoixCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return GroupeChoix::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Groupe de choix')->setEntityLabelInPlural('Groupes de choix');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('menu', 'Menu');
        yield $this->texte('libelle', 'Libellé', 80);
    }
}
