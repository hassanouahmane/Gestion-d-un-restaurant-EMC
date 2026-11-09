<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Validator\Constraints as Assert;

abstract class CatalogueCrudController extends AbstractCrudController
{
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::DELETE, Action::BATCH_DELETE);
    }

    protected function texte(string $propriete, string $titre, int $longueur = 150): TextField
    {
        return TextField::new($propriete, $titre)->setMaxLength($longueur)->setFormTypeOption('constraints', [
            new Assert\NotBlank(), new Assert\Length(max: $longueur),
        ]);
    }

    protected function decimal(string $propriete, string $titre, bool $prix = false): NumberField
    {
        return NumberField::new($propriete, $titre)
            ->setStoredAsString()
            ->setNumDecimals($prix ? 2 : 4)
            ->setFormTypeOption('constraints', [
                new Assert\NotBlank(),
                new Assert\Range(min: $prix ? 0 : 0.0001, max: $prix ? '9999999999.99' : '9999999999.9999'),
            ]);
    }

    protected function unite(string $propriete, string $titre): ChoiceField
    {
        return ChoiceField::new($propriete, $titre)->setChoices([
            'Gramme' => 'g', 'Kilogramme' => 'kg', 'Millilitre' => 'ml', 'Centilitre' => 'cl',
            'Litre' => 'l', 'Pièce' => 'piece', 'Portion' => 'portion',
        ]);
    }
}
