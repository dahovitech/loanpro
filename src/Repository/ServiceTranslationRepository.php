<?php

namespace App\Repository;

use App\Entity\ServiceTranslation;
use App\Entity\Language;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceTranslation>
 */
class ServiceTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceTranslation::class);
    }

    public function findByServiceAndLanguage(Service $service, Language $language): ?ServiceTranslation
    {
        return $this->createQueryBuilder('st')
            ->where('st.service = :service')
            ->andWhere('st.language = :language')
            ->setParameter('service', $service)
            ->setParameter('language', $language)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByServiceAndLanguageCode(Service $service, string $languageCode): ?ServiceTranslation
    {
        return $this->createQueryBuilder('st')
            ->join('st.language', 'l')
            ->where('st.service = :service')
            ->andWhere('l.code = :languageCode')
            ->setParameter('service', $service)
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findCompletedTranslations(Service $service): array
    {
        return $this->createQueryBuilder('st')
            ->join('st.language', 'l')
            ->addSelect('l')
            ->where('st.service = :service')
            ->andWhere('st.title IS NOT NULL')
            ->andWhere('st.title != :empty')
            ->setParameter('service', $service)
            ->setParameter('empty', '')
            ->orderBy('l.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findIncompleteTranslations(Service $service): array
    {
        return $this->createQueryBuilder('st')
            ->join('st.language', 'l')
            ->addSelect('l')
            ->where('st.service = :service')
            ->andWhere(
                $this->createQueryBuilder('st')->expr()->orX(
                    'st.title IS NULL',
                    'st.title = :empty'
                )
            )
            ->setParameter('service', $service)
            ->setParameter('empty', '')
            ->orderBy('l.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTranslationStats(): array
    {
        $totalServices = $this->createQueryBuilder('st')
            ->select('COUNT(DISTINCT st.service)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalLanguages = $this->createQueryBuilder('st')
            ->select('COUNT(DISTINCT st.language)')
            ->getQuery()
            ->getSingleScalarResult();

        $completedTranslations = $this->createQueryBuilder('st')
            ->select('COUNT(st.id)')
            ->where('st.title IS NOT NULL')
            ->andWhere('st.title != :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPossibleTranslations = $totalServices * $totalLanguages;

        return [
            'totalServices' => $totalServices,
            'totalLanguages' => $totalLanguages,
            'completedTranslations' => $completedTranslations,
            'totalPossibleTranslations' => $totalPossibleTranslations,
            'completionPercentage' => $totalPossibleTranslations > 0 
                ? round(($completedTranslations / $totalPossibleTranslations) * 100, 2)
                : 0,
        ];
    }
}
