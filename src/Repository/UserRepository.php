<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
* @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Permet d'obtenir le nombre d'utilisateurs
     */
    public function countUsers(): int{
        /* Query qui permet d'obtenir le nombre d'utilisateurs total */
        $totalUsers = $this->createQueryBuilder('u') // Création d'un query builder avec un alias pour l'entité actuel
                    ->select('COUNT(u.id)') // Sélection du nombre d'id des utilisateurs
                    ->getQuery() // Obtention de la query construite
                    ->getSingleScalarResult(); // Execution de la query et obtention du résultat sous forme d'un nombre
        
        return $totalUsers;
    }

    /**
     * Permet d'obtenir les utilisateurs bannis ou non bannis
     */
    public function getUsersByStatus(bool $isBanned){
        /* Query qui permet d'obtenir les utilisateurs non bannis */
        $usersByStatus = $this->createQueryBuilder('u') // Création d'un query builder avec un alias pour l'entité actuel
                        ->select('u.id, u.email, u.pseudo, u.dateInscription, u.roles'); // Sélection de l'id, email, pseudo, date d'inscription et rôles des utilisateurs
        
        /* Si on veut les utilisateurs bannis */                        
        if($isBanned){
            $usersByStatus->andWhere('u.estBanni = true'); // On restreint la query aux utilisateurs bannis
        }
        else{
            $usersByStatus->andWhere('u.estBanni = false'); // On restreint la query aux utilisateurs non bannis
        }

        return $usersByStatus->getQuery()->getResult(); // Obtention de la query construite, execution de la query et obtention du résultat
    }

    /**
     * Permet d'obtenir les utilisateurs non vérifiés
     */
    public function getUnverifiedUsers(){
        /* Query qui permet d'obtenir les utilisateurs non vérifiés */
        $unverifiedUsers = $this->createQueryBuilder('u') // Création d'un query builder avec un alias pour l'entité actuel
                            ->select('u.id, u.email, u.pseudo, u.dateInscription') // Sélection de l'id, email, pseudo et date d'inscription des utilisateurs
                            ->andWhere('u.isVerified = false') // On restreint la query aux utilisateurs non vérifié
                            ->getQuery() // Obtention de la query construite
                            ->getResult(); // Execution de la query et obtention du résultat

        return $unverifiedUsers;
    }

//    /**
//     * @return User[] Returns an array of User objects
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

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
