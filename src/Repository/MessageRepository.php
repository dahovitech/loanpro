<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all conversations for a user
     * @return array
     */
    public function findConversations(User $user): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select('DISTINCT IDENTITY(m.sender) as sender_id, IDENTITY(m.recipient) as recipient_id')
            ->where('m.sender = :user OR m.recipient = :user')
            ->setParameter('user', $user);

        $results = $qb->getQuery()->getResult();
        
        $userIds = [];
        foreach ($results as $result) {
            if ($result['sender_id'] != $user->getId()) {
                $userIds[] = $result['sender_id'];
            }
            if ($result['recipient_id'] != $user->getId()) {
                $userIds[] = $result['recipient_id'];
            }
        }

        $userIds = array_unique($userIds);

        if (empty($userIds)) {
            return [];
        }

        // Récupérer les utilisateurs et leurs derniers messages
        $conversations = [];
        $userRepository = $this->getEntityManager()->getRepository(User::class);
        
        foreach ($userIds as $userId) {
            $otherUser = $userRepository->find($userId);
            if ($otherUser) {
                $lastMessage = $this->createQueryBuilder('m')
                    ->where('(m.sender = :user AND m.recipient = :other) OR (m.sender = :other AND m.recipient = :user)')
                    ->setParameter('user', $user)
                    ->setParameter('other', $otherUser)
                    ->orderBy('m.sentAt', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                $unreadCount = $this->createQueryBuilder('m')
                    ->select('COUNT(m.id)')
                    ->where('m.sender = :other AND m.recipient = :user AND m.isRead = false')
                    ->setParameter('user', $user)
                    ->setParameter('other', $otherUser)
                    ->getQuery()
                    ->getSingleScalarResult();

                $conversations[] = [
                    'user' => $otherUser,
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount
                ];
            }
        }

        // Trier par date du dernier message
        usort($conversations, function($a, $b) {
            $aTime = $a['last_message'] ? $a['last_message']->getSentAt()->getTimestamp() : 0;
            $bTime = $b['last_message'] ? $b['last_message']->getSentAt()->getTimestamp() : 0;
            return $bTime - $aTime;
        });

        return $conversations;
    }

    /**
     * Find messages between two users
     * @return Message[]
     */
    public function findConversationMessages(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread messages for a user
     */
    public function countUnreadMessages(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.recipient = :user AND m.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find recent messages for a user
     * @return Message[]
     */
    public function findRecentMessages(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}