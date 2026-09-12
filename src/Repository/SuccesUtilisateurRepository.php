<?php
namespace App\Repository;
use App\Entity\SuccesUtilisateur;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
final class SuccesUtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuccesUtilisateur::class);
    }

    public function trouverPour(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('d')->addSelect('s')->join('d.succes', 's')->andWhere('d.utilisateur = :u')->setParameter('u', $utilisateur)->orderBy('d.debloqueLe', 'DESC')->getQuery()->getResult();
    }

    /** @return array<int, float> Taux d'obtention indexés par identifiant de succès. */
    public function trouverTauxObtention(): array
    {
        $limiteActivite = new \DateTimeImmutable('-25 months');
        $nombreMembres = (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(utilisateur.id)')
            ->from(Utilisateur::class, 'utilisateur')
            ->andWhere('COALESCE(utilisateur.derniereActivite, utilisateur.inscritLe) >= :limiteActivite')
            ->andWhere('utilisateur.experience > 0')
            ->setParameter('limiteActivite', $limiteActivite)
            ->getQuery()
            ->getSingleScalarResult();

        if ($nombreMembres === 0) {
            return [];
        }

        $resultats = $this->createQueryBuilder('deblocage')
            ->select('IDENTITY(deblocage.succes) AS succesId, COUNT(deblocage.id) AS nombre')
            ->join('deblocage.utilisateur', 'utilisateur')
            ->andWhere('COALESCE(utilisateur.derniereActivite, utilisateur.inscritLe) >= :limiteActivite')
            ->andWhere('utilisateur.experience > 0')
            ->setParameter('limiteActivite', $limiteActivite)
            ->groupBy('deblocage.succes')
            ->getQuery()
            ->getArrayResult();

        $taux = [];
        foreach ($resultats as $resultat) {
            $taux[(int) $resultat['succesId']] = round(((int) $resultat['nombre'] / $nombreMembres) * 100, 1);
        }

        return $taux;
    }
}
