<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Permet d'obtenir les utilisateurs qui ont créer le plus de post
     */
    public function usersMostPostsCreated(){
        /* Query qui permet d'obtenir les utilisateurs avec le plus de post créé */
        $usersMostPostsCreated = $this->createQueryBuilder('p') // Création d'un query builder avec un alias pour identifier l'entité actuel
                                ->select('u.id AS userId, u.username AS username, COUNT(p.id) as postsCount') // Sélection des ids et pseudos des utilisateurs et du nombre de posts
                                ->join('p.user', 'u') // Association avec les utilisateurs
                                ->groupby('p.user') // Grouper par les utilisateurs
                                ->orderBy('postsCount', 'DESC') // Ordre décroissant du nombre de post
                                ->setMaxResults(10) // Définition d'un nombre de résultats max
                                ->getQuery() // Obtention de la query construite
                                ->getResult(); // Execution de la query et obtention des résultats

        return $usersMostPostsCreated;                                
    }

//    /**
//     * @return Post[] Returns an array of Post objects
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

//    public function findOneBySomeField($value): ?Post
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
