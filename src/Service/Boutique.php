<?php

namespace App\Service;

use App\Entity\AchatBoutique;
use App\Entity\ArticleBoutique;
use App\Entity\Utilisateur;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Boutique
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function acheter(Utilisateur $utilisateur, ArticleBoutique $article): AchatBoutique
    {
        return $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($utilisateur, $article): AchatBoutique {
            $membreVerrouille = $entityManager->find(Utilisateur::class, $utilisateur->getId(), LockMode::PESSIMISTIC_WRITE);
            $articleVerrouille = $entityManager->find(ArticleBoutique::class, $article->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$membreVerrouille instanceof Utilisateur || !$articleVerrouille instanceof ArticleBoutique || !$articleVerrouille->isDisponible()) { throw new \DomainException('Cet article n’est plus disponible.'); }
            if ($entityManager->getRepository(AchatBoutique::class)->findOneBy(['utilisateur' => $membreVerrouille, 'article' => $articleVerrouille])) { throw new \DomainException('Tu possèdes déjà cet article.'); }
            if ($membreVerrouille->getPoints() < $articleVerrouille->getPrix()) { throw new \DomainException('Tu n’as pas assez de points pour cet achat.'); }

            $membreVerrouille->setPoints($membreVerrouille->getPoints() - $articleVerrouille->getPrix());
            $articleVerrouille->retirerDuStock();
            $achat = (new AchatBoutique())->setUtilisateur($membreVerrouille)->setArticle($articleVerrouille)->setPrixPaye($articleVerrouille->getPrix());
            $entityManager->persist($achat);

            return $achat;
        });
    }
}
