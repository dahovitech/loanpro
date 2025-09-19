<?php

namespace App\Repository;

use App\Entity\Media;
use App\Entity\Loan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 *
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * Trouve tous les médias validés
     */
    public function findValidated(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.isValidated = :validated')
            ->setParameter('validated', true)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les médias non validés
     */
    public function findPendingValidation(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.isValidated = :validated')
            ->setParameter('validated', false)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les médias par type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.type = :type')
            ->setParameter('type', $type)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les médias pour une demande de prêt
     */
    public function findByLoan(Loan $loan): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.loans', 'l')
            ->andWhere('l.id = :loanId')
            ->setParameter('loanId', $loan->getId())
            ->orderBy('m.type', 'ASC')
            ->addOrderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les médias par type pour une demande de prêt
     */
    public function findByLoanAndType(Loan $loan, string $type): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.loans', 'l')
            ->andWhere('l.id = :loanId')
            ->andWhere('m.type = :type')
            ->setParameter('loanId', $loan->getId())
            ->setParameter('type', $type)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les médias par type pour une demande
     */
    public function countByLoanAndType(Loan $loan, string $type): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->join('m.loans', 'l')
            ->andWhere('l.id = :loanId')
            ->andWhere('m.type = :type')
            ->setParameter('loanId', $loan->getId())
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les médias récents (derniers 30 jours)
     */
    public function findRecent(int $limit = 50): array
    {
        $date = new \DateTime('-30 days');
        
        return $this->createQueryBuilder('m')
            ->andWhere('m.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les médias orphelins (non liés à une demande)
     */
    public function findOrphans(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.loans', 'l')
            ->andWhere('l.id IS NULL')
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les médias par extension
     */
    public function findByExtension(string $extension): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.extension = :extension')
            ->setParameter('extension', $extension)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des médias par type
     */
    public function getStatsByType(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.type, COUNT(m.id) as count, SUM(m.fileSize) as totalSize')
            ->groupBy('m.type')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de médias par nom de fichier ou description
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.originalFileName LIKE :query OR m.fileName LIKE :query OR m.description LIKE :query OR m.alt LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les gros fichiers (> taille spécifiée en bytes)
     */
    public function findLargeFiles(int $minSize = 5242880): array // 5MB par défaut
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.fileSize > :minSize')
            ->setParameter('minSize', $minSize)
            ->orderBy('m.fileSize', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nettoie les médias anciens et orphelins
     */
    public function findOldOrphans(int $daysOld = 30): array
    {
        $date = new \DateTime("-{$daysOld} days");
        
        return $this->createQueryBuilder('m')
            ->leftJoin('m.loans', 'l')
            ->andWhere('l.id IS NULL')
            ->andWhere('m.createdAt < :date')
            ->setParameter('date', $date)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Media $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Media $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
