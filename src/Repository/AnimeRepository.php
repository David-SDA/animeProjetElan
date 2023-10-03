<?php

namespace App\Repository;

use App\Entity\Anime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Anime>
 *
 * @method Anime|null find($id, $lockMode = null, $lockVersion = null)
 * @method Anime|null findOneBy(array $criteria, array $orderBy = null)
 * @method Anime[]    findAll()
 * @method Anime[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anime::class);
    }

    /**
     * Permet d'obtenir le nombre d'animé (dans la base de données)
     */
    public function countAnimes(): int{
        /* Query qui permet d'obtenir le nombre d'animé total dans la base de données */
        $totalAnimes = $this->createQueryBuilder('a') // Création d'un query builder avec un alias pour identifier l'entité actuel
                    ->select('COUNT(a.id)') // Sélection du nombre d'id des animées
                    ->getQuery() // Obtention de la query construite
                    ->getSingleScalarResult(); // Execution de la query et obtention des résultat sous forme d'un nombre
        
        return $totalAnimes;
    }

//    /**
//     * @return Anime[] Returns an array of Anime objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Anime
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
