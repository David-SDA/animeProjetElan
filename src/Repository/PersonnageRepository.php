<?php

namespace App\Repository;

use App\Entity\Personnage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Personnage>
 *
 * @method Personnage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Personnage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Personnage[]    findAll()
 * @method Personnage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PersonnageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personnage::class);
    }

    /**
     * Permet d'obtenir le nombre de personnages (dans la base de données)
     */
    public function countCharacters(): int{
        /* Query qui permet d'obtenir le nombre de personnages total dans la base de données */
        $totalCharacters = $this->createQueryBuilder('p') // Création d'un query builder avec un alias pour identifier l'entité actuel
                    ->select('COUNT(p.id)') // Sélection du nombre d'id des personnages
                    ->getQuery() // Obtention de la query construite
                    ->getSingleScalarResult(); // Execution de la query et obtention du résultat sous forme d'un nombre
        
        return $totalCharacters;
    }

//    /**
//     * @return Personnage[] Returns an array of Personnage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Personnage
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
