<?php

namespace App\Controller\Admin;

use App\Entity\Loan;
use App\Repository\LoanRepository;
use App\Repository\MediaRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/admin/loans')]
class AdminLoanController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoanRepository $loanRepository,
        private MediaRepository $mediaRepository,
        private MailerInterface $mailer,
        private NotificationService $notificationService
    ) {}

    #[Route('', name: 'admin_loan_index')]
    public function index(Request $request): Response
    {
        $query = $request->query->get('q');
        $status = $request->query->get('status');
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        
        if ($query || $status) {
            $loans = $this->loanRepository->search($query, $status, $page, $limit);
            $total = $this->loanRepository->countSearch($query, $status);
        } else {
            $loans = $this->loanRepository->findBy(
                [], 
                ['createdAt' => 'DESC'], 
                $limit, 
                ($page - 1) * $limit
            );
            $total = $this->loanRepository->count([]);
        }

        $statistics = $this->loanRepository->getStatistics();
        $maxPages = ceil($total / $limit);

        return $this->render('admin/loan/index.html.twig', [
            'loans' => $loans,
            'statistics' => $statistics,
            'currentQuery' => $query,
            'currentStatus' => $status,
            'currentPage' => $page,
            'maxPages' => $maxPages,
            'total' => $total,
        ]);
    }

    #[Route('/{id}', name: 'admin_loan_show')]
    public function show(Loan $loan): Response
    {
        // Récupérer les documents associés
        $documents = $this->mediaRepository->findBy(['loan' => $loan]);
        
        // Calculer des métriques pour ce prêt
        $metrics = $this->calculateLoanMetrics($loan);
        
        // Vérifier les documents requis
        $requiredDocuments = [
            'identity' => 'Pièce d\'identité',
            'income' => 'Justificatif de revenus',
            'residence' => 'Justificatif de domicile',
            'bank' => 'RIB',
        ];
        
        $missingDocuments = [];
        foreach ($requiredDocuments as $type => $label) {
            $hasDocument = false;
            foreach ($documents as $doc) {
                if ($doc->getType() === $type) {
                    $hasDocument = true;
                    break;
                }
            }
            if (!$hasDocument) {
                $missingDocuments[] = $label;
            }
        }

        return $this->render('admin/loan/show.html.twig', [
            'loan' => $loan,
            'documents' => $documents,
            'metrics' => $metrics,
            'missing_documents' => $missingDocuments,
            'required_documents' => $requiredDocuments,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_loan_edit')]
    public function edit(Request $request, Loan $loan): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            if (isset($data['status'])) {
                $oldStatus = $loan->getStatus();
                $loan->setStatus($data['status']);
                
                // Log du changement de statut
                $this->logStatusChange($loan, $oldStatus, $data['status']);
            }
            
            if (isset($data['interestRate'])) {
                $loan->setInterestRate($data['interestRate']);
                $loan->calculateMonthlyPayment();
            }
            
            if (isset($data['notes'])) {
                $loan->setNotes($data['notes']);
            }

            // Champs supplémentaires pour un suivi complet
            if (isset($data['admin_comments'])) {
                $loan->setAdminComments($data['admin_comments']);
            }

            $this->entityManager->flush();
            
            $this->addFlash('success', 'Le prêt a été mis à jour avec succès.');
            
            return $this->redirectToRoute('admin_loan_show', ['id' => $loan->getId()]);
        }

        return $this->render('admin/loan/edit.html.twig', [
            'loan' => $loan,
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_loan_approve')]
    public function approve(Request $request, Loan $loan): Response
    {
        $interestRate = $request->request->get('interest_rate', '5.0');
        $adminComments = $request->request->get('admin_comments', '');
        
        $oldStatus = $loan->getStatus();
        $loan->setStatus('approved');
        $loan->setInterestRate($interestRate);
        $loan->setAdminComments($adminComments);
        $loan->setApprovedAt(new \DateTime());
        $loan->calculateMonthlyPayment();
        
        $this->entityManager->flush();
        
        // Envoyer notification via le service
        $this->notificationService->notifyLoanEvent($loan, 'loan_approved', [
            'interest_rate' => $interestRate,
            'admin_comments' => $adminComments
        ]);
        
        // Log du changement
        $this->logStatusChange($loan, $oldStatus, 'approved');
        
        $this->addFlash('success', sprintf(
            'Le prêt de %s %s a été approuvé avec un taux de %s%%.',
            $loan->getFirstName(),
            $loan->getLastName(),
            $interestRate
        ));
        
        return $this->redirectToRoute('admin_loan_index');
    }

    #[Route('/{id}/reject', name: 'admin_loan_reject')]
    public function reject(Request $request, Loan $loan): Response
    {
        $reason = $request->request->get('reason', '');
        $adminComments = $request->request->get('admin_comments', '');
        
        $oldStatus = $loan->getStatus();
        $loan->setStatus('rejected');
        $loan->setRejectionReason($reason);
        $loan->setAdminComments($adminComments);
        $loan->setRejectedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        // Envoyer notification via le service
        $this->notificationService->notifyLoanEvent($loan, 'loan_rejected', [
            'rejection_reason' => $reason,
            'admin_comments' => $adminComments
        ]);
        
        // Log du changement
        $this->logStatusChange($loan, $oldStatus, 'rejected');
        
        $this->addFlash('success', sprintf(
            'Le prêt de %s %s a été rejeté.',
            $loan->getFirstName(),
            $loan->getLastName()
        ));
        
        return $this->redirectToRoute('admin_loan_index');
    }

    #[Route('/{id}/request-documents', name: 'admin_loan_request_documents')]
    public function requestDocuments(Request $request, Loan $loan): Response
    {
        $requestedDocuments = $request->request->get('documents', []);
        $message = $request->request->get('message', '');
        
        $loan->setStatus('documents_requested');
        $loan->setRequestedDocuments($requestedDocuments);
        $loan->setAdminComments($message);
        
        $this->entityManager->flush();
        
        // Envoyer notification via le service
        $this->notificationService->notifyLoanEvent($loan, 'documents_requested', [
            'requested_documents' => $requestedDocuments,
            'message' => $message
        ]);
        
        $this->addFlash('success', 'Demande de documents envoyée au client.');
        
        return $this->redirectToRoute('admin_loan_show', ['id' => $loan->getId()]);
    }

    #[Route('/{id}/archive', name: 'admin_loan_archive')]
    public function archive(Loan $loan): Response
    {
        $loan->setStatus('archived');
        $loan->setArchivedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Le dossier a été archivé.');
        
        return $this->redirectToRoute('admin_loan_index');
    }

    #[Route('/{id}/delete', name: 'admin_loan_delete', methods: ['POST'])]
    public function delete(Loan $loan): Response
    {
        // Supprimer aussi les documents associés
        $documents = $this->mediaRepository->findBy(['loan' => $loan]);
        foreach ($documents as $document) {
            $this->entityManager->remove($document);
        }
        
        $this->entityManager->remove($loan);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Le prêt et ses documents ont été supprimés.');
        
        return $this->redirectToRoute('admin_loan_index');
    }

    #[Route('/batch/action', name: 'admin_loan_batch_action', methods: ['POST'])]
    public function batchAction(Request $request): Response
    {
        $action = $request->request->get('action');
        $loanIds = $request->request->get('loan_ids', []);
        
        if (empty($loanIds)) {
            $this->addFlash('error', 'Aucun prêt sélectionné.');
            return $this->redirectToRoute('admin_loan_index');
        }
        
        $loans = $this->loanRepository->findBy(['id' => $loanIds]);
        $count = 0;
        
        switch ($action) {
            case 'approve':
                foreach ($loans as $loan) {
                    if ($loan->getStatus() === 'pending') {
                        $loan->setStatus('approved');
                        $loan->setApprovedAt(new \DateTime());
                        $count++;
                    }
                }
                break;
                
            case 'reject':
                foreach ($loans as $loan) {
                    if ($loan->getStatus() === 'pending') {
                        $loan->setStatus('rejected');
                        $loan->setRejectedAt(new \DateTime());
                        $count++;
                    }
                }
                break;
                
            case 'archive':
                foreach ($loans as $loan) {
                    $loan->setStatus('archived');
                    $loan->setArchivedAt(new \DateTime());
                    $count++;
                }
                break;
        }
        
        $this->entityManager->flush();
        
        $this->addFlash('success', sprintf('%d prêt(s) traité(s) avec succès.', $count));
        
        return $this->redirectToRoute('admin_loan_index');
    }

    #[Route('/api/quick-stats', name: 'admin_loan_api_stats')]
    public function apiQuickStats(): JsonResponse
    {
        $stats = [
            'pending' => $this->loanRepository->count(['status' => 'pending']),
            'approved_today' => $this->loanRepository->countApprovedToday(),
            'total_amount_today' => $this->loanRepository->getTotalAmountToday(),
            'documents_to_validate' => $this->mediaRepository->count(['status' => 'pending']),
        ];
        
        return $this->json($stats);
    }

    private function calculateLoanMetrics(Loan $loan): array
    {
        $documents = $this->mediaRepository->findBy(['loan' => $loan]);
        
        return [
            'documents_count' => count($documents),
            'documents_validated' => count(array_filter($documents, fn($d) => $d->getStatus() === 'validated')),
            'time_since_creation' => $loan->getCreatedAt()->diff(new \DateTime())->days,
            'completion_percentage' => $this->calculateCompletionPercentage($loan),
            'risk_score' => $this->calculateRiskScore($loan),
        ];
    }

    private function calculateCompletionPercentage(Loan $loan): int
    {
        $totalSteps = 4; // Documents requis de base
        $completedSteps = 0;
        
        $documents = $this->mediaRepository->findBy(['loan' => $loan]);
        $documentTypes = array_map(fn($d) => $d->getType(), $documents);
        
        $requiredTypes = ['identity', 'income', 'residence', 'bank'];
        foreach ($requiredTypes as $type) {
            if (in_array($type, $documentTypes)) {
                $completedSteps++;
            }
        }
        
        return round(($completedSteps / $totalSteps) * 100);
    }

    private function calculateRiskScore(Loan $loan): string
    {
        $score = 0;
        
        // Montant du prêt
        if ($loan->getAmount() > 50000) $score += 2;
        elseif ($loan->getAmount() > 25000) $score += 1;
        
        // Durée
        if ($loan->getDuration() > 84) $score += 2;
        elseif ($loan->getDuration() > 60) $score += 1;
        
        // Age du dossier
        $daysSinceCreation = $loan->getCreatedAt()->diff(new \DateTime())->days;
        if ($daysSinceCreation > 7) $score += 1;
        
        if ($score >= 4) return 'high';
        elseif ($score >= 2) return 'medium';
        else return 'low';
    }

    private function logStatusChange(Loan $loan, string $oldStatus, string $newStatus): void
    {
        // Ici, vous pouvez implémenter un système de logs plus sophistiqué
        error_log(sprintf(
            'Loan #%d status changed from %s to %s by user %s',
            $loan->getId(),
            $oldStatus,
            $newStatus,
            $this->getUser()?->getEmail() ?? 'system'
        ));
    }

}
