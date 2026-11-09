<?php

namespace App\Controller;

use App\Service\Allergenes;
use App\Service\Restaurant;
use App\Service\Unites;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class RestaurantController extends AbstractController
{
    public function __construct(private Connection $db, private Restaurant $restaurant)
    {
    }

    #[Route('/', name: 'accueil', methods: ['GET'])]
    public function accueil(Allergenes $allergenes): Response
    {
        $plats = $this->restaurant->carte();
        foreach ($plats as $id => &$plat) {
            $plat['allergenes'] = $allergenes->pourPlat((int) $id);
        }

        return $this->render('restaurant/carte.html.twig', ['plats' => $plats, 'menus' => $this->restaurant->menus()]);
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

    #[Route('/gestion', name: 'gestion', methods: ['GET'])]
    public function gestion(): Response
    {
        return $this->render('restaurant/gestion.html.twig', [
            'ventes' => $this->restaurant->ventes(), 'suggestions' => $this->restaurant->suggestions(),
        ]);
    }

    #[Route('/gestion/stock', name: 'gestion_stock', methods: ['GET', 'POST'])]
    public function stock(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->verifierFormulaire($request, 'stock');
            try {
                $quantite = $this->quantite($request);
                $ingredient = $request->request->getInt('ingredient');
                if ($request->request->getString('operation') === 'perte') {
                    $this->restaurant->perte($ingredient, $quantite, $request->request->getString('motif'));
                } else {
                    $this->restaurant->compter($ingredient, $quantite);
                }
                $this->addFlash('succes', 'Opération enregistrée.');
            } catch (\DomainException $exception) {
                $this->addFlash('erreur', $exception->getMessage());
            }

            return $this->redirectToRoute('gestion_stock');
        }

        return $this->render('restaurant/stock.html.twig', [
            'stocks' => $this->restaurant->stocks(),
            'inventaires' => $this->db->fetchAllAssociative(
                'SELECT v.*, i.nom, i.unite_stock, v.stock_compte - v.stock_theorique AS ecart
                 FROM inventaire v JOIN ingredient i ON i.id = v.ingredient_id ORDER BY v.id DESC LIMIT 50',
            ),
            'mouvements' => $this->db->fetchAllAssociative(
                'SELECT m.*, i.nom, i.unite_stock FROM mouvement_stock m JOIN ingredient i ON i.id = m.ingredient_id ORDER BY m.id DESC LIMIT 50',
            ),
        ]);
    }

    #[Route('/gestion/achats', name: 'gestion_achats', methods: ['GET', 'POST'])]
    public function achats(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->verifierFormulaire($request, 'achats');
            try {
                match ($request->request->getString('operation')) {
                    'creer' => $this->creerAchat($request),
                    'ligne' => $this->ajouterLigneAchat($request),
                    'recevoir' => $this->restaurant->recevoir($request->request->getInt('achat')),
                    default => throw new \DomainException('Opération inconnue.'),
                };
                $this->addFlash('succes', 'Achat mis à jour.');
            } catch (\DomainException $exception) {
                $this->addFlash('erreur', $exception->getMessage());
            }

            return $this->redirectToRoute('gestion_achats');
        }

        return $this->render('restaurant/achats.html.twig', [
            'fournisseurs' => $this->db->fetchAllAssociative('SELECT id, nom FROM fournisseur ORDER BY nom'),
            'ingredients' => $this->restaurant->stocks(),
            'produits' => $this->db->fetchAllAssociative('SELECT id, nom, code_barres FROM produit_achete ORDER BY nom'),
            'achats' => $this->db->fetchAllAssociative(
                'SELECT a.id, a.date_achat, a.statut, f.nom FROM achat a JOIN fournisseur f ON f.id = a.fournisseur_id ORDER BY a.id DESC',
            ),
            'lignes' => $this->db->fetchAllAssociative(
                'SELECT l.*, i.nom FROM ligne_achat l JOIN ingredient i ON i.id = l.ingredient_id ORDER BY l.id DESC',
            ),
        ]);
    }

    #[Route('/gestion/allergenes', name: 'gestion_allergenes', methods: ['GET', 'POST'])]
    public function allergenes(Request $request, Allergenes $allergenes): Response
    {
        if ($request->isMethod('POST')) {
            $this->verifierFormulaire($request, 'allergenes');
            try {
                if ($request->request->getString('operation') === 'rechercher') {
                    $allergenes->rattacher($request->request->getInt('ingredient'), trim($request->request->getString('code')));
                } else {
                    $allergenes->valider($request->request->getInt('produit'), $request->request->getString('decision'));
                }
                $this->addFlash('succes', 'Fiche produit mise à jour.');
            } catch (\DomainException $exception) {
                $this->addFlash('erreur', $exception->getMessage());
            }

            return $this->redirectToRoute('gestion_allergenes');
        }
        $produits = $this->db->fetchAllAssociative(
            'SELECT p.*, i.nom AS ingredient FROM produit_achete p JOIN ingredient i ON i.id = p.ingredient_id ORDER BY p.nom',
        );
        foreach ($produits as &$produit) {
            $produit['allergenes'] = $produit['allergenes'] === null ? null : json_decode($produit['allergenes'], true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->render('restaurant/allergenes.html.twig', [
            'ingredients' => $this->restaurant->stocks(), 'produits' => $produits,
        ]);
    }

    private function creerAchat(Request $request): void
    {
        $fournisseur = $request->request->getInt('fournisseur');
        if (!$this->db->fetchOne('SELECT 1 FROM fournisseur WHERE id = ?', [$fournisseur])) {
            throw new \DomainException('Fournisseur inconnu.');
        }
        $this->db->insert('achat', [
            'fournisseur_id' => $fournisseur, 'statut' => 'brouillon',
            'date_achat' => (new \DateTimeImmutable('now', new \DateTimeZone('Africa/Casablanca')))->format('Y-m-d'),
        ]);
    }

    private function ajouterLigneAchat(Request $request): void
    {
        $this->db->transactional(function () use ($request): void {
            $this->db->fetchFirstColumn('SELECT id FROM ingredient ORDER BY id FOR UPDATE');
            $achat = $request->request->getInt('achat');
            if ($this->db->fetchOne('SELECT statut FROM achat WHERE id = ? FOR UPDATE', [$achat]) !== 'brouillon') {
                throw new \DomainException('Cet achat ne peut plus être modifié.');
            }
            $ingredient = $request->request->getInt('ingredient');
            $unite = $request->request->getString('unite');
            $quantite = $this->quantite($request);
            $prix = $this->quantite($request, 'prix');
            $stockUnite = $this->db->fetchOne('SELECT unite_stock FROM ingredient WHERE id = ?', [$ingredient]);
            if (!$stockUnite || $quantite <= 0 || $prix > 9999999999.99) {
                throw new \DomainException('Ingrédient, quantité ou prix invalide.');
            }
            $convertie = Unites::convertir($quantite, $unite, $stockUnite);
            if ($convertie < 0.00005 || $convertie > 9999999999.9999) {
                throw new \DomainException('La quantité convertie dépasse la précision du stock.');
            }
            $produit = $request->request->getInt('produit');
            if ($produit && (int) $this->db->fetchOne('SELECT ingredient_id FROM produit_achete WHERE id = ?', [$produit]) !== $ingredient) {
                throw new \DomainException('Ce produit doit être rattaché à l’ingrédient acheté.');
            }
            $this->db->insert('ligne_achat', [
                'achat_id' => $achat, 'ingredient_id' => $ingredient, 'unite_achat' => $unite,
                'quantite' => Unites::decimal($quantite), 'prix_unitaire' => Unites::decimal($prix, 2),
                'produit_achete_id' => $produit ?: null,
            ]);
        });
    }

    private function verifierFormulaire(Request $request, string $action): void
    {
        if (!$this->isCsrfTokenValid($action, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de formulaire invalide.');
        }
    }

    private function quantite(Request $request, string $champ = 'quantite'): float
    {
        $nombre = str_replace(',', '.', trim($request->request->getString($champ)));
        if (!preg_match('/^\d{1,10}(\.\d{1,4})?$/D', $nombre)) {
            throw new \DomainException('Entre une quantité ou un prix valide.');
        }

        return (float) $nombre;
    }
}
