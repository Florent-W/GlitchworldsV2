<?php

namespace App\Service;

use App\Entity\AchatBoutique;
use App\Entity\ArticleBoutique;
use App\Entity\Utilisateur;
use App\Enum\TypeArticleBoutique;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Boutique
{
    public const MAX_VITRINES = 3;

    public function __construct(private EntityManagerInterface $entityManager, private ProgressionUtilisateur $progression) {}

    public function acheter(Utilisateur $utilisateur, ArticleBoutique $article): AchatBoutique
    {
        return $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($utilisateur, $article): AchatBoutique {
            $membreVerrouille = $entityManager->find(Utilisateur::class, $utilisateur->getId(), LockMode::PESSIMISTIC_WRITE);
            $articleVerrouille = $entityManager->find(ArticleBoutique::class, $article->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$membreVerrouille instanceof Utilisateur || !$articleVerrouille instanceof ArticleBoutique || !$articleVerrouille->isDisponible()) { throw new \DomainException('Cet article n’est plus disponible.'); }
            $achatExistant = $entityManager->getRepository(AchatBoutique::class)->findOneBy(['utilisateur' => $membreVerrouille, 'article' => $articleVerrouille]);
            if ($achatExistant && $articleVerrouille->getType() !== TypeArticleBoutique::Vitrine) { throw new \DomainException('Tu possèdes déjà cet article.'); }
            if ($achatExistant && $articleVerrouille->getType() === TypeArticleBoutique::Vitrine && $achatExistant->getQuantite() >= self::MAX_VITRINES) { throw new \DomainException('Tu possèdes déjà le maximum de trois vitrines.'); }
            $numeroAchat = $achatExistant ? $achatExistant->getQuantite() + 1 : 1;
            $dejaDebite = $this->progression->achatBoutiqueDejaDebite($membreVerrouille, (int) $articleVerrouille->getId(), $numeroAchat);
            if (!$dejaDebite && $membreVerrouille->getPoints() < $articleVerrouille->getPrix()) { throw new \DomainException('Tu n’as pas assez de points pour cet achat.'); }
            if (!$dejaDebite) { $this->progression->debiterBoutique($membreVerrouille, (int) $articleVerrouille->getId(), $articleVerrouille->getNom(), $articleVerrouille->getPrix(), $numeroAchat); }
            $articleVerrouille->retirerDuStock();
            $achat = $achatExistant ?: (new AchatBoutique())->setUtilisateur($membreVerrouille)->setArticle($articleVerrouille)->setPrixPaye($articleVerrouille->getPrix());
            if ($achatExistant) { $achat->ajouterUnite(); } else { $entityManager->persist($achat); }

            return $achat;
        });
    }
}
