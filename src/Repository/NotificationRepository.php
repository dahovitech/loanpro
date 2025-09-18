<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function save(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Notification[] Returns an array of Notification objects for a specific user
     */
    public function findByUser(User $user, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Notification[] Returns an array of unread notifications for a user
     */
    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get count of unread notifications for a user
     */
    public function getUnreadCountByUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Notification[] Returns recent notifications for a user
     */
    public function findRecentByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsReadForUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->set('n.readAt', ':readAt')
            ->where('n.user = :user')
            ->andWhere('n.isRead = :currentlyUnread')
            ->setParameter('isRead', true)
            ->setParameter('readAt', new \DateTime())
            ->setParameter('user', $user)
            ->setParameter('currentlyUnread', false)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete old read notifications
     */
    public function deleteOldReadNotifications(int $daysOld = 30): int
    {
        $date = new \DateTime();
        $date->sub(new \DateInterval('P' . $daysOld . 'D'));

        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.isRead = :isRead')
            ->andWhere('n.readAt < :date')
            ->setParameter('isRead', true)
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Find notifications by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.type = :type')
            ->setParameter('type', $type)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find notifications by type for a specific user
     */
    public function findByTypeAndUser(string $type, User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.type = :type')
            ->andWhere('n.user = :user')
            ->setParameter('type', $type)
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('n');
        
        return [
            'total_count' => $qb->select('COUNT(n.id)')->getQuery()->getSingleScalarResult(),
            'unread_count' => $this->createQueryBuilder('n')
                ->select('COUNT(n.id)')
                ->where('n.isRead = :isRead')
                ->setParameter('isRead', false)
                ->getQuery()
                ->getSingleScalarResult(),
            'read_count' => $this->createQueryBuilder('n')
                ->select('COUNT(n.id)')
                ->where('n.isRead = :isRead')
                ->setParameter('isRead', true)
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    /**
     * Create a new notification
     */
    public function createNotification(User $user, string $title, string $message, string $type = 'info', string $actionUrl = null, string $actionLabel = null): Notification
    {
        $notification = new Notification();
        $notification->setUser($user)
            ->setTitle($title)
            ->setMessage($message)
            ->setType($type)
            ->setActionUrl($actionUrl)
            ->setActionLabel($actionLabel);

        $this->save($notification, true);

        return $notification;
    }
}
