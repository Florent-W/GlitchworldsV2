<?php
namespace App\Controller;
use App\Entity\Jeu;
use App\Entity\JeuBibliotheque;
use App\Entity\ListeJeux;
use App\Entity\Utilisateur;
use App\Enum\StatutBibliotheque;
use App\Enum\StatutJeu;
use App\Repository\AvisRepository;
use App\Repository\JeuBibliothequeRepository;
use App\Repository\JeuRepository;
use App\Repository\ListeJeuxRepository;
use App\Repository\SuccesRepository;
use App\Repository\SuccesUtilisateurRepository;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/mes-jeux')]
final class MesJeuxController extends AbstractController
{
    use AnnonceSuccesTrait;
    #[Route('', name: 'app_mes_jeux', methods: ['GET'])]
    public function index(Request $request, JeuBibliothequeRepository $bibliotheque, ListeJeuxRepository $listes, JeuRepository $jeux, SuccesRepository $succes, SuccesUtilisateurRepository $deblocages, GestionSucces $gestionSucces, AvisRepository $avisRepository): Response
    {
        $utilisateur = $this->membre();
        $this->annoncerSucces($gestionSucces->verifier($utilisateur));
        $acquis = $deblocages->trouverPour($utilisateur);
        $bibliothequeJeux = $bibliotheque->trouverPour($utilisateur);
        $listesJeux = $listes->trouverPour($utilisateur);
        $rechercheListes = trim($request->query->getString('q'));
        $listesPubliques = $listes->trouverPubliquesPaginees(1, 6, $rechercheListes)['listes'];
        $jeuxDansLesListes = [];
        foreach ($listesJeux as $liste) {
            foreach ($liste->getJeux() as $jeu) {
                $jeuxDansLesListes[] = $jeu;
            }
        }

        return $this->render('mes_jeux/index.html.twig', [
            'bibliotheque' => $bibliothequeJeux,
            'notesJeux' => $avisRepository->trouverResumesPour(array_values(array_filter(
                array_map(static fn ($entree) => $entree->getJeu(), $bibliothequeJeux),
                static fn ($jeu) => $jeu !== null,
            ))),
            'listes' => $listesJeux,
            'listesPubliques' => $listesPubliques,
            'rechercheListes' => $rechercheListes,
            'jeuxDisponibles' => $jeux->findBy(['statut' => StatutJeu::Approuve], ['nom' => 'ASC']),
            'notesPersonnelles' => $avisRepository->trouverNotesUtilisateurPour($jeuxDansLesListes, $utilisateur),
            'succes' => $succes->trouverTousParDifficulte(),
            'succesAcquis' => $acquis,
            'codesAcquis' => array_map(static fn ($d) => $d->getSucces()?->getCode(), $acquis),
            'tauxSucces' => $deblocages->trouverTauxObtention(),
            'statuts' => StatutBibliotheque::cases(),
        ]);
    }

    #[Route('/jeu/{id}', name: 'app_mes_jeux_ajouter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ajouter(Jeu $jeu, Request $request, EntityManagerInterface $em, GestionSucces $succes): Response
    {
        $utilisateur = $this->membre(); $this->csrf('bibliotheque-'.$jeu->getId(), $request);
        $entree = $em->getRepository(JeuBibliotheque::class)->findOneBy(['utilisateur' => $utilisateur, 'jeu' => $jeu]) ?? (new JeuBibliotheque())->setUtilisateur($utilisateur)->setJeu($jeu);
        $statut = StatutBibliotheque::tryFrom($request->request->getString('statut')) ?? StatutBibliotheque::A_Jouer;
        $entree->setStatut($statut); $em->persist($entree); $em->flush();
        $this->verifierEtAnnoncerSucces($utilisateur, $succes);
        $this->addFlash('success', $jeu->getNom().' est dans « Mes jeux » : '.$statut->label().'.');
        return $this->redirectToRoute('app_mes_jeux', ['onglet' => 'bibliotheque']);
    }

    #[Route('/jeu/{id}/retirer', name: 'app_mes_jeux_retirer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retirer(JeuBibliotheque $entree, Request $request, EntityManagerInterface $em): Response
    {
        $this->verifierProprietaire($entree->getUtilisateur());
        $this->csrf('retirer-bibliotheque-'.$entree->getId(), $request);
        $em->remove($entree);
        $em->flush();

        return $this->redirectToRoute('app_mes_jeux', ['onglet' => 'bibliotheque']);
    }

    #[Route('/listes', name: 'app_liste_jeux_creer', methods: ['POST'])]
    public function creerListe(Request $request, EntityManagerInterface $em, GestionSucces $succes, SluggerInterface $slugger): Response
    {
        $this->csrf('creer-liste', $request);
        $utilisateur = $this->membre();
        $nom = trim($request->request->getString('nom'));
        if ($nom !== '' && mb_strlen($nom) <= 80) {
            $liste = (new ListeJeux())->setUtilisateur($utilisateur)->setNom($nom)->setDescription($request->request->getString('description') ?: null);
            if ($request->request->getString('visibilite', 'publique') !== 'privee') {
                $slug = strtolower($slugger->slug($nom)->toString());
                $liste->setSlug($slug !== '' ? $slug : 'liste')->setPublique(true);
            }
            $em->persist($liste);
            $em->flush();
            $this->verifierEtAnnoncerSucces($utilisateur, $succes);
            $this->addFlash('success', $liste->isPublique() ? 'Liste publique créée et prête à être partagée.' : 'Liste privée créée.');
        }

        return $this->redirectToRoute('app_mes_jeux', ['onglet' => 'listes', '_fragment' => 'listes']);
    }

    #[Route('/listes/{id}/jeu/{jeuId}', name: 'app_liste_jeux_basculer', requirements: ['id' => '\d+', 'jeuId' => '\d+'], methods: ['POST'])]
    public function basculerListe(ListeJeux $liste, int $jeuId, Request $request, EntityManagerInterface $em): Response
    {
        $this->verifierProprietaire($liste->getUtilisateur());
        $this->csrf('liste-'.$liste->getId().'-jeu-'.$jeuId, $request);

        $jeu = $em->find(Jeu::class, $jeuId);
        if (!$jeu || $jeu->getStatut() !== StatutJeu::Approuve) {
            throw $this->createNotFoundException();
        }

        if ($liste->contient($jeu)) {
            $liste->retirerJeu($jeu);
        } else {
            $liste->ajouterJeu($jeu);
        }
        $liste->touch();

        $em->flush();

        return $this->redirectToRoute('app_mes_jeux', ['onglet' => 'listes', '_fragment' => 'listes']);
    }

    #[Route('/listes/{id}/jeux', name: 'app_liste_jeux_ajouter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ajouterAListe(ListeJeux $liste, Request $request, EntityManagerInterface $em): Response
    {
        $this->verifierProprietaire($liste->getUtilisateur());
        $this->csrf('liste-ajouter-'.$liste->getId(), $request);

        $jeu = $em->find(Jeu::class, $request->request->getInt('jeu_id'));
        if (!$jeu || $jeu->getStatut() !== StatutJeu::Approuve) {
            throw $this->createNotFoundException('Ce jeu ne peut pas être ajouté à une liste.');
        }

        $liste->ajouterJeu($jeu);
        $liste->touch();
        $em->flush();
        $this->addFlash('success', $jeu->getNom().' a été ajouté à « '.$liste->getNom().' ».');

        return $this->redirectToRoute('app_mes_jeux', ['onglet' => 'listes', '_fragment' => 'listes']);
    }

    #[Route('/listes/{id}/jeu/{jeuId}/deplacer/{direction}', name: 'app_liste_jeux_deplacer', requirements: ['id' => '\d+', 'jeuId' => '\d+', 'direction' => 'haut|bas'], methods: ['POST'])]
    public function deplacerDansListe(ListeJeux $liste, int $jeuId, string $direction, Request $request, EntityManagerInterface $em): Response
    {
        $this->verifierProprietaire($liste->getUtilisateur());
        $this->csrf('liste-'.$liste->getId().'-deplacer-'.$jeuId, $request);

        $jeu = $em->find(Jeu::class, $jeuId);
        if (!$jeu || !$liste->contient($jeu)) {
            throw $this->createNotFoundException();
        }

        $liste->deplacerJeu($jeu, $direction === 'haut' ? -1 : 1);
        $liste->touch();
        $em->flush();

        return $this->redirectToRoute('app_mes_jeux', ['onglet' => 'listes', '_fragment' => 'listes']);
    }

    #[Route('/listes/{id}/ordre', name: 'app_liste_jeux_ordre', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function enregistrerOrdre(ListeJeux $liste, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->verifierProprietaire($liste->getUtilisateur());
        $donnees = $request->toArray();
        if (!$this->isCsrfTokenValid('liste-'.$liste->getId().'-ordre', (string) ($donnees['_token'] ?? ''))) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $ordre = array_map('intval', is_array($donnees['ordre'] ?? null) ? $donnees['ordre'] : []);
        if (!$liste->reordonnerJeux($ordre)) {
            return $this->json(['message' => 'Ordre invalide.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $liste->touch();

        $em->flush();

        return $this->json(['enregistre' => true]);
    }

    #[Route('/listes/{id}/partage', name: 'app_liste_jeux_partage', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function basculerPartage(ListeJeux $liste, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->verifierProprietaire($liste->getUtilisateur());
        $this->csrf('partage-liste-'.$liste->getId(), $request);
        if (!$liste->isPublique()) {
            $slug = strtolower($slugger->slug($liste->getNom())->toString());
            $liste->setSlug($slug !== '' ? $slug : 'liste')->setPublique(true);
            $this->addFlash('success', 'La liste est maintenant publique et partageable.');
        } else {
            $liste->setPublique(false);
            $this->addFlash('success', 'La liste est maintenant privée.');
        }
        $em->flush();

        return $this->redirectToRoute('app_mes_jeux', ['onglet' => 'listes', '_fragment' => 'listes']);
    }

    #[Route('/listes/{id}/supprimer', name: 'app_liste_jeux_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimerListe(ListeJeux $liste, Request $request, EntityManagerInterface $em): Response
    {
        $this->verifierProprietaire($liste->getUtilisateur());
        $this->csrf('supprimer-liste-'.$liste->getId(), $request);
        $em->remove($liste);
        $em->flush();

        return $this->redirectToRoute('app_mes_jeux');
    }

    private function membre(): Utilisateur
    {
        $membre = $this->getUser();
        if (!$membre instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        return $membre;
    }

    private function verifierProprietaire(?Utilisateur $proprietaire): void
    {
        if ($proprietaire !== $this->membre()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function csrf(string $id, Request $request): void
    {
        if (!$this->isCsrfTokenValid($id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
