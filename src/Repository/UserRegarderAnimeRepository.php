<?php

namespace App\Repository;

use App\Entity\UserRegarderAnime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRegarderAnime>
 *
 * @method UserRegarderAnime|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRegarderAnime|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRegarderAnime[]    findAll()
 * @method UserRegarderAnime[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRegarderAnimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRegarderAnime::class);
    }

//    /**
//     * @return UserRegarderAnime[] Returns an array of UserRegarderAnime objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserRegarderAnime
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
