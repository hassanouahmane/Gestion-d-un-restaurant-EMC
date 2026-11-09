<?php

namespace App\Controller;

use App\Service\Restaurant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class RestaurantController extends AbstractController
{
    public function __construct(private \Doctrine\DBAL\Connection $db, private Restaurant $restaurant)
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

    #[Route('/salle', name: 'salle', methods: ['GET', 'POST'])]
    public function salle(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->verifierFormulaire($request, 'commande');
            try {
                $id = $this->restaurant->commander($request->request->getInt('table'), $request->request->all('articles'));
                $this->addFlash('succes', "Commande #$id envoyée en cuisine.");

                return $this->redirectToRoute('salle');
            } catch (\DomainException $exception) {
                $this->addFlash('erreur', $exception->getMessage());
            }
        }

        return $this->render('restaurant/salle.html.twig', [
            'tables' => $this->db->fetchAllAssociative('SELECT id, numero, capacite FROM table_salle ORDER BY id'),
            'plats' => $this->restaurant->carte(), 'menus' => $this->restaurant->menus(),
        ]);
    }

    #[Route('/cuisine', name: 'cuisine', methods: ['GET'])]
    public function cuisine(): Response
    {
        return $this->render('restaurant/cuisine.html.twig', ['service' => $this->restaurant->service()]);
    }

    #[Route('/cuisine/{id}/statut', name: 'cuisine_statut', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function statut(Request $request, int $id): Response
    {
        $this->verifierFormulaire($request, "service-$id");
        try {
            $this->restaurant->changerStatut($id, $request->request->getString('statut'));
            $this->addFlash('succes', 'Statut mis à jour.');
        } catch (\DomainException $exception) {
            $this->addFlash('erreur', $exception->getMessage());
        }

        return $this->redirectToRoute('cuisine');
    }

    private function verifierFormulaire(Request $request, string $action): void
    {
        if (!$this->isCsrfTokenValid($action, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de formulaire invalide.');
        }
    }
}
