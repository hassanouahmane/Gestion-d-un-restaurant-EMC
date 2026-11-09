<?php

namespace App\Controller;

use App\Service\Restaurant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class RestaurantController extends AbstractController
{
    public function __construct(private Restaurant $restaurant)
    {
    }

    #[Route('/', name: 'accueil', methods: ['GET'])]
    public function accueil(): Response
    {
        return $this->render('restaurant/carte.html.twig', [
            'plats' => $this->restaurant->carte(), 'menus' => $this->restaurant->menus(),
        ]);
    }

    #[Route('/connexion', name: 'connexion', methods: ['GET', 'POST'])]
    public function connexion(AuthenticationUtils $authentication): Response
    {
        return $this->render('restaurant/connexion.html.twig', [
            'identifiant' => $authentication->getLastUsername(), 'erreur' => $authentication->getLastAuthenticationError() !== null,
        ]);
    }

    #[Route('/deconnexion', name: 'deconnexion', methods: ['POST'])]
    public function deconnexion(): never
    {
        throw new \LogicException('La déconnexion est traitée par le pare-feu.');
    }
}
