<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_plat_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Gestion du restaurant');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Tableau de bord', 'fa fa-chart-line', 'gestion');
        yield MenuItem::section('Carte et recettes');
        yield MenuItem::linkTo(PlatCrudController::class, 'Plats', 'fa fa-utensils');
        yield MenuItem::linkTo(IngredientCrudController::class, 'Ingrédients');
        yield MenuItem::linkTo(PreparationCrudController::class, 'Préparations');
        yield MenuItem::linkTo(RecetteCrudController::class, 'Recettes');
        yield MenuItem::linkTo(DoseIngredientCrudController::class, 'Doses d’ingrédients');
        yield MenuItem::linkTo(DosePreparationCrudController::class, 'Doses de préparation');
        yield MenuItem::linkTo(MenuCrudController::class, 'Menus');
        yield MenuItem::linkTo(GroupeChoixCrudController::class, 'Groupes de choix');
        yield MenuItem::linkTo(OptionMenuCrudController::class, 'Options de menu');
        yield MenuItem::section('Restaurant');
        yield MenuItem::linkTo(TableSalleCrudController::class, 'Tables');
        yield MenuItem::linkTo(FournisseurCrudController::class, 'Fournisseurs');
        yield MenuItem::linkToRoute('Achats', 'fa fa-truck', 'gestion_achats');
        yield MenuItem::linkToRoute('Stocks', 'fa fa-boxes-stacked', 'gestion_stock');
    }
}
