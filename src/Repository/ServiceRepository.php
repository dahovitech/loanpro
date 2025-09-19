<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findActiveServices(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.translations', 't')
            ->leftJoin('t.language', 'l')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithTranslations(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.translations', 't')
            ->leftJoin('t.language', 'l')
            ->addSelect('t', 'l')
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveBySlug(string $slug): ?Service
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.translations', 't')
            ->leftJoin('t.language', 'l')
            ->addSelect('t', 'l')
            ->where('s.slug = :slug')
            ->andWhere('s.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySlugWithTranslations(string $slug): ?Service
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.translations', 't')
            ->leftJoin('t.language', 'l')
            ->addSelect('t', 'l')
            ->where('s.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForAdministration(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.translations', 't')
            ->leftJoin('t.language', 'l')
            ->addSelect('t', 'l')
            ->orderBy('s.updatedAt', 'DESC')
            ->addOrderBy('s.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query, string $languageCode = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.translations', 't')
            ->leftJoin('t.language', 'l')
            ->addSelect('t', 'l')
            ->where('s.isActive = :active')
            ->setParameter('active', true);

        if ($languageCode) {
            $qb->andWhere('l.code = :languageCode')
                ->setParameter('languageCode', $languageCode);
        }

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('t.title', ':query'),
                $qb->expr()->like('t.description', ':query'),
                $qb->expr()->like('s.slug', ':query')
            )
        )
        ->setParameter('query', '%' . $query . '%')
        ->orderBy('s.sortOrder', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
