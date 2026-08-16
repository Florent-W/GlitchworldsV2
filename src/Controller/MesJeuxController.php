<?php
namespace App\Controller;
use App\Entity\Jeu;
use App\Entity\JeuBibliotheque;
use App\Entity\ListeJeux;
use App\Entity\Utilisateur;
use App\Enum\StatutBibliotheque;
use App\Repository\JeuBibliothequeRepository;
use App\Repository\ListeJeuxRepository;
use App\Repository\SuccesRepository;
use App\Repository\SuccesUtilisateurRepository;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mes-jeux')]
final class MesJeuxController extends AbstractController
{
    #[Route('', name: 'app_mes_jeux', methods: ['GET'])]
    public function index(JeuBibliothequeRepository $bibliotheque, ListeJeuxRepository $listes, SuccesRepository $succes, SuccesUtilisateurRepository $deblocages, GestionSucces $gestionSucces): Response
    {
        $utilisateur = $this->membre(); $gestionSucces->verifier($utilisateur);
        $acquis = $deblocages->trouverPour($utilisateur);
        return $this->render('mes_jeux/index.html.twig', ['bibliotheque' => $bibliotheque->trouverPour($utilisateur), 'listes' => $listes->trouverPour($utilisateur), 'succes' => $succes->findAll(), 'succesAcquis' => $acquis, 'codesAcquis' => array_map(static fn ($d) => $d->getSucces()?->getCode(), $acquis), 'statuts' => StatutBibliotheque::cases()]);
    }

    #[Route('/jeu/{id}', name: 'app_mes_jeux_ajouter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ajouter(Jeu $jeu, Request $request, EntityManagerInterface $em, GestionSucces $succes): Response
    {
        $utilisateur = $this->membre(); $this->csrf('bibliotheque-'.$jeu->getId(), $request);
        $entree = $em->getRepository(JeuBibliotheque::class)->findOneBy(['utilisateur' => $utilisateur, 'jeu' => $jeu]) ?? (new JeuBibliotheque())->setUtilisateur($utilisateur)->setJeu($jeu);
        $statut = StatutBibliotheque::tryFrom($request->request->getString('statut')) ?? StatutBibliotheque::A_Jouer;
        $entree->setStatut($statut); $em->persist($entree); $em->flush(); $succes->verifier($utilisateur);
        $this->addFlash('success', $jeu->getNom().' est dans « Mes jeux » : '.$statut->label().'.');
        return $this->redirectToRoute('app_mes_jeux');
    }

    #[Route('/jeu/{id}/retirer', name: 'app_mes_jeux_retirer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retirer(JeuBibliotheque $entree, Request $request, EntityManagerInterface $em): Response { $this->verifierProprietaire($entree->getUtilisateur()); $this->csrf('retirer-bibliotheque-'.$entree->getId(), $request); $em->remove($entree); $em->flush(); return $this->redirectToRoute('app_mes_jeux'); }

    #[Route('/listes', name: 'app_liste_jeux_creer', methods: ['POST'])]
    public function creerListe(Request $request, EntityManagerInterface $em): Response { $this->csrf('creer-liste', $request); $nom = trim($request->request->getString('nom')); if ($nom !== '' && mb_strlen($nom) <= 80) { $em->persist((new ListeJeux())->setUtilisateur($this->membre())->setNom($nom)->setDescription($request->request->getString('description') ?: null)); $em->flush(); $this->addFlash('success', 'Liste créée.'); } return $this->redirectToRoute('app_mes_jeux', ['_fragment' => 'listes']); }

    #[Route('/listes/{id}/jeu/{jeuId}', name: 'app_liste_jeux_basculer', requirements: ['id' => '\d+', 'jeuId' => '\d+'], methods: ['POST'])]
    public function basculerListe(ListeJeux $liste, int $jeuId, Request $request, EntityManagerInterface $em): Response { $this->verifierProprietaire($liste->getUtilisateur()); $this->csrf('liste-'.$liste->getId().'-jeu-'.$jeuId, $request); $jeu = $em->find(Jeu::class, $jeuId); if (!$jeu) { throw $this->createNotFoundException(); } $liste->contient($jeu) ? $liste->retirerJeu($jeu) : $liste->ajouterJeu($jeu); $em->flush(); return $this->redirectToRoute('app_mes_jeux', ['_fragment' => 'listes']); }

    #[Route('/listes/{id}/supprimer', name: 'app_liste_jeux_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimerListe(ListeJeux $liste, Request $request, EntityManagerInterface $em): Response { $this->verifierProprietaire($liste->getUtilisateur()); $this->csrf('supprimer-liste-'.$liste->getId(), $request); $em->remove($liste); $em->flush(); return $this->redirectToRoute('app_mes_jeux'); }

    private function membre(): Utilisateur { $membre = $this->getUser(); if (!$membre instanceof Utilisateur) { throw $this->createAccessDeniedException(); } return $membre; }
    private function verifierProprietaire(?Utilisateur $proprietaire): void { if ($proprietaire !== $this->membre()) { throw $this->createAccessDeniedException(); } }
    private function csrf(string $id, Request $request): void { if (!$this->isCsrfTokenValid($id, $request->request->getString('_token'))) { throw $this->createAccessDeniedException('Jeton CSRF invalide.'); } }
}
