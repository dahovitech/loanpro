<?php

namespace App\Repository;

use App\Entity\ConfigTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigTranslation>
 */
class ConfigTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigTranslation::class);
    }

    /**
     * Find translations for a specific config and language
     */
    public function findByConfigAndLanguage(int $configId, int $languageId): ?ConfigTranslation
    {
        return $this->createQueryBuilder('ct')
            ->where('ct.config = :configId')
            ->andWhere('ct.language = :languageId')
            ->setParameter('configId', $configId)
            ->setParameter('languageId', $languageId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all translations for a specific language
     */
    public function findByLanguage(int $languageId): array
    {
        return $this->createQueryBuilder('ct')
            ->join('ct.config', 'c')
            ->where('ct.language = :languageId')
            ->andWhere('c.isActive = :active')
            ->setParameter('languageId', $languageId)
            ->setParameter('active', true)
            ->orderBy('c.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
