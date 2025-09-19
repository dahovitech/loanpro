<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 *
 * @method PasswordResetToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method PasswordResetToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method PasswordResetToken[]    findAll()
 * @method PasswordResetToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function save(PasswordResetToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PasswordResetToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find a valid token by its value
     */
    public function findValidToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('prt')
            ->where('prt.token = :token')
            ->andWhere('prt.isUsed = false')
            ->andWhere('prt.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find existing valid tokens for a user
     */
    public function findValidTokensForUser(User $user): array
    {
        return $this->createQueryBuilder('prt')
            ->where('prt.user = :user')
            ->andWhere('prt.isUsed = false')
            ->andWhere('prt.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Remove all tokens for a user (used when password is reset)
     */
    public function invalidateAllTokensForUser(User $user): void
    {
        $this->createQueryBuilder('prt')
            ->update()
            ->set('prt.isUsed', 'true')
            ->where('prt.user = :user')
            ->andWhere('prt.isUsed = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Clean up expired tokens (should be run periodically)
     */
    public function removeExpiredTokens(): int
    {
        $result = $this->createQueryBuilder('prt')
            ->delete()
            ->where('prt.expiresAt <= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();

        return $result;
    }

    /**
     * Count how many valid tokens a user has requested recently (to prevent spam)
     */
    public function countRecentTokensForUser(User $user, int $minutes = 15): int
    {
        $since = new \DateTime(sprintf('-%d minutes', $minutes));
        
        return $this->createQueryBuilder('prt')
            ->select('COUNT(prt.id)')
            ->where('prt.user = :user')
            ->andWhere('prt.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}