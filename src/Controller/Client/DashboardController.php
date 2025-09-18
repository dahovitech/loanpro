<?php

namespace App\Controller\Client;

use App\Entity\Loan;
use App\Entity\User;
use App\Repository\LoanRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private LoanRepository $loanRepository,
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'client_dashboard')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer les statistiques du client
        $loans = $this->loanRepository->findBy(['user' => $user]);
        $activeLoans = $this->loanRepository->findBy(['user' => $user, 'status' => ['pending', 'approved']]);
        $notifications = $this->notificationRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 5);
        
        $stats = [
            'total_loans' => count($loans),
            'active_loans' => count($activeLoans),
            'total_amount' => array_sum(array_map(fn($loan) => $loan->getAmount(), $loans)),
            'pending_count' => count($this->loanRepository->findBy(['user' => $user, 'status' => 'pending'])),
            'approved_count' => count($this->loanRepository->findBy(['user' => $user, 'status' => 'approved'])),
            'rejected_count' => count($this->loanRepository->findBy(['user' => $user, 'status' => 'rejected']))
        ];

        return $this->render('client/dashboard/index.html.twig', [
            'user' => $user,
            'loans' => $loans,
            'active_loans' => $activeLoans,
            'notifications' => $notifications,
            'stats' => $stats
        ]);
    }

    #[Route('/loans', name: 'client_loans')]
    public function loans(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $loans = $this->loanRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('client/dashboard/loans.html.twig', [
            'loans' => $loans
        ]);
    }

    #[Route('/loan/{id}', name: 'client_loan_detail')]
    public function loanDetail(Loan $loan): Response
    {
        // Vérifier que le prêt appartient à l'utilisateur connecté
        if ($loan->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('client/dashboard/loan_detail.html.twig', [
            'loan' => $loan
        ]);
    }

    #[Route('/notifications', name: 'client_notifications')]
    public function notifications(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('client/dashboard/notifications.html.twig', [
            'notifications' => $notifications
        ]);
    }

    #[Route('/api/loan-status/{id}', name: 'api_loan_status', methods: ['GET'])]
    public function getLoanStatus(Loan $loan): JsonResponse
    {
        // Vérifier que le prêt appartient à l'utilisateur connecté
        if ($loan->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return new JsonResponse([
            'id' => $loan->getId(),
            'status' => $loan->getStatus(),
            'amount' => $loan->getAmount(),
            'interest_rate' => $loan->getInterestRate(),
            'duration' => $loan->getDuration(),
            'created_at' => $loan->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $loan->getUpdatedAt()?->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/api/notifications/unread', name: 'api_notifications_unread', methods: ['GET'])]
    public function getUnreadNotifications(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $unreadNotifications = $this->notificationRepository->findBy([
            'user' => $user,
            'isRead' => false
        ], ['createdAt' => 'DESC']);

        $notifications = array_map(function($notification) {
            return [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'created_at' => $notification->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $unreadNotifications);

        return new JsonResponse([
            'notifications' => $notifications,
            'count' => count($notifications)
        ]);
    }

    #[Route('/api/notifications/{id}/read', name: 'api_notification_read', methods: ['POST'])]
    public function markNotificationAsRead(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $notification = $this->notificationRepository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$notification) {
            return new JsonResponse(['error' => 'Notification not found'], 404);
        }

        $notification->setIsRead(true);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}