<?php
namespace App\Controller;
use App\Entity\CategorieJeu;
use App\Entity\Genre;
use App\Entity\Langue;
use App\Entity\Plateforme;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
#[Route('/administration/referentiels', name: 'app_administration_referentiels_')]
final class AdministrationReferentielController extends AbstractController
{
    private const TYPES = ['categories' => [CategorieJeu::class, 'Catégories'], 'genres' => [Genre::class, 'Genres'], 'langues' => [Langue::class, 'Langues'], 'plateformes' => [Plateforme::class, 'Plateformes']];

    #[Route('/{type}', name: 'liste', requirements: ['type' => 'categories|genres|langues|plateformes'], methods: ['GET'])]
    public function liste(string $type, EntityManagerInterface $em): Response
    {
        [$classe, $titre] = self::TYPES[$type];

        return $this->render('administration/referentiels.html.twig', [
            'type' => $type,
            'titre' => $titre,
            'elements' => $em->getRepository($classe)->findBy([], ['nom' => 'ASC']),
            'types' => self::TYPES,
        ]);
    }
    #[Route('/{type}/enregistrer', name: 'enregistrer', requirements: ['type' => 'categories|genres|langues|plateformes'], methods: ['POST'])]
    public function enregistrer(string $type, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if (!$this->isCsrfTokenValid('referentiel-'.$type, $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        [$classe] = self::TYPES[$type]; $id = $request->request->getInt('id'); $element = $id > 0 ? $em->find($classe, $id) : new $classe();
        $nom = trim($request->request->getString('nom')); if (!$element || $nom === '' || mb_strlen($nom) > 80) { $this->addFlash('danger', 'Le nom doit contenir entre 1 et 80 caractères.'); return $this->redirectToRoute('app_administration_referentiels_liste', ['type' => $type]); }
        $base = strtolower($slugger->slug($nom)->toString()) ?: 'element'; $slug = $base; $suffixe = 2;
        while (($existant = $em->getRepository($classe)->findOneBy(['slug' => $slug])) && $existant !== $element) { $slug = $base.'-'.$suffixe++; }
        $element->setNom($nom)->setSlug($slug); $em->persist($element); $em->flush(); $this->addFlash('success', $id ? 'Élément modifié.' : 'Élément ajouté.');
        return $this->redirectToRoute('app_administration_referentiels_liste', ['type' => $type]);
    }
    #[Route('/{type}/{id}/supprimer', name: 'supprimer', requirements: ['type' => 'categories|genres|langues|plateformes', 'id' => '\\d+'], methods: ['POST'])]
    public function supprimer(string $type, int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('supprimer-referentiel-'.$type.'-'.$id, $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        [$classe] = self::TYPES[$type]; $element = $em->find($classe, $id); if (!$element) { throw $this->createNotFoundException(); }
        try { $em->remove($element); $em->flush(); $this->addFlash('success', 'Élément supprimé.'); } catch (ForeignKeyConstraintViolationException) { $em->clear(); $this->addFlash('danger', 'Impossible de supprimer cet élément : il est encore utilisé par un ou plusieurs jeux.'); }
        return $this->redirectToRoute('app_administration_referentiels_liste', ['type' => $type]);
    }
}
