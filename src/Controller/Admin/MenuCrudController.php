<?php

namespace App\Controller\Admin;

use App\Entity\Menu;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

final class MenuCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return Menu::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Menu')->setEntityLabelInPlural('Menus');
    }

    public function configureFields(string $pageName): iterable
    {
        yield $this->texte('nom', 'Nom');
        yield $this->decimal('prixVente', 'Prix de vente', prix: true);
    }
}
