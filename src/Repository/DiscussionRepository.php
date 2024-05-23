<?php

namespace App\Repository;

use App\Entity\Discussion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Discussion>
 *
 * @method Discussion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Discussion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Discussion[]    findAll()
 * @method Discussion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscussionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discussion::class);
    }

    /**
     * Permet d'obtenir les discussions non liées à un anime (avec option de tri/ordre)
     */
    public function discussionsWithoutAnime(string $orderBy, string $order): array{
        return $this->createQueryBuilder('d')
            ->leftJoin('d.anime','a')
            ->andWhere('a.id IS NULL')
            ->orderBy('d.' . $orderBy, $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * Permet d'obtenir le nombre de discussions
     */
    public function countDiscussions(): int{
        /* Query qui permet d'obtenir le nombre de discussion total */
        $totalTalks = $this->createQueryBuilder('d') // Création d'un query builder avec un alias pour identifier l'entité actuel
                    ->select('COUNT(d.id)') // Sélection du nombre d'id des discussions
                    ->getQuery() // Obtention de la query construite
                    ->getSingleScalarResult(); // Execution de la query et obtention du résultat sous forme d'un nombre
        
        return $totalTalks;
    }

    /**
     * Permet d'obtenir les utilisateurs qui ont créer le plus de discussions
     */
    public function usersMostTalksCreated(){
        /* Query qui permet d'obtenir les utilisateurs avec le plus de discussions créé */
        $usersMostTalksCreated = $this->createQueryBuilder('d') // Création d'un query builder avec un alias pour identifier l'entité actuel
                                ->select('u.id AS userId, u.username AS username, COUNT(d.id) as talksCount') // Sélection des ids et pseudos des utilisateurs et du nombre de discussions
                                ->join('d.user', 'u') // Association avec les utilisateurs
                                ->groupby('d.user') // Grouper par les utilisateurs
                                ->orderBy('talksCount', 'DESC') // Ordre décroissant du nombre de discussion
                                ->setMaxResults(10) // Définition d'un nombre de résultats max
                                ->getQuery() // Obtention de la query construite
                                ->getResult(); // Execution de la query et obtention des résultats

        return $usersMostTalksCreated;                                
    }

    /**
     * Permet d'obtenir toutes les discussions en fonction du nombre de post
     */
    public function talksByPostsNumber(string $sort){
        /* Query qui permet d'obtenir les discussions dans l'ordre du nombre de posts dans ceux-ci */
        $talks = $this->createQueryBuilder('d') // Création d'un query builder avec un alias pour identifier l'entité actuel
                    ->leftJoin('d.posts', 'p') // Association avec les posts
                    ->leftJoin('d.anime', 'a') // Association avec les anime
                    ->groupBy('d.id') // Grouper par les discussions
                    ->orderBy('COUNT(p.id)', $sort) // Ordre sur le nombre de posts de chaque discussion
                    ->andWhere('a.id IS NULL') // Discussion non lié à un anime
                    ->getQuery() // Obtention de la query construite
                    ->getResult(); // Execution de la query et obtention des résultats

        return $talks;
    }

//    /**
//     * @return Discussion[] Returns an array of Discussion objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Discussion
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
