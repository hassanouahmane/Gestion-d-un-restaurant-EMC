<?php

namespace App\Controller\Admin;

use App\Entity\TableSalle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Component\Validator\Constraints as Assert;

final class TableSalleCrudController extends CatalogueCrudController
{
    public static function getEntityFqcn(): string
    {
        return TableSalle::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Table')->setEntityLabelInPlural('Tables');
    }

    public function configureFields(string $pageName): iterable
    {
        yield $this->texte('numero', 'Numéro', 20);
        yield IntegerField::new('capacite', 'Capacité')->setFormTypeOption('constraints', [new Assert\NotBlank(), new Assert\Range(min: 1, max: 60)]);
    }
}
