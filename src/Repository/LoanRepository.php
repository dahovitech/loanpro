<?php

namespace App\Repository;

use App\Entity\Loan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 *
 * @method Loan|null find($id, $lockMode = null, $lockVersion = null)
 * @method Loan|null findOneBy(array $criteria, array $orderBy = null)
 * @method Loan[]    findAll()
 * @method Loan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    public function save(Loan $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Loan $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Loan[] Returns an array of Loan objects for a specific user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Loan[] Returns an array of Loan objects with specific status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->setParameter('status', $status)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Loan[] Returns an array of active loans
     */
    public function findActiveLoans(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status IN (:statuses)')
            ->setParameter('statuses', ['approved', 'active'])
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Loan[] Returns an array of pending loans
     */
    public function findPendingLoans(): array
    {
        return $this->findByStatus('pending');
    }

    /**
     * Get total amount of loans for a user
     */
    public function getTotalAmountByUser(User $user): float
    {
        $result = $this->createQueryBuilder('l')
            ->select('SUM(l.amount)')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Get count of loans by status for a user
     */
    public function getCountByStatusAndUser(string $status, User $user): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.status = :status')
            ->andWhere('l.user = :user')
            ->setParameter('status', $status)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get loans statistics
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('l');
        
        return [
            'total_count' => $qb->select('COUNT(l.id)')->getQuery()->getSingleScalarResult(),
            'total_amount' => $qb->select('SUM(l.amount)')->getQuery()->getSingleScalarResult() ?? 0,
            'pending_count' => $this->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.status = :status')
                ->setParameter('status', 'pending')
                ->getQuery()
                ->getSingleScalarResult(),
            'approved_count' => $this->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.status = :status')
                ->setParameter('status', 'approved')
                ->getQuery()
                ->getSingleScalarResult(),
            'active_count' => $this->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.status IN (:statuses)')
                ->setParameter('statuses', ['approved', 'active'])
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    /**
     * Find loans created within a date range
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.createdAt >= :start_date')
            ->andWhere('l.createdAt <= :end_date')
            ->setParameter('start_date', $startDate)
            ->setParameter('end_date', $endDate)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent loans for dashboard
     */
    public function getRecentLoans(int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
