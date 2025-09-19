<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Repository\LoanRepository;
use App\Repository\MediaRepository;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/client')]
class ClientDashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoanRepository $loanRepository,
        private MediaRepository $mediaRepository,
        private NotificationRepository $notificationRepository,
        private NotificationService $notificationService
    ) {}

    #[Route('/dashboard', name: 'client_dashboard')]
    public function dashboard(Request $request): Response
    {
        // Pour la démo, on utilise l'email de session ou un paramètre
        // En production, cela viendrait de l'authentification utilisateur
        $email = $request->getSession()->get('client_email') ?? $request->query->get('email');
        
        if (!$email) {
            // Rediriger vers une page de connexion ou demander l'email
            return $this->render('client/email_request.html.twig');
        }

        // Récupérer les prêts du client
        $loans = $this->loanRepository->findBy(
            ['email' => $email], 
            ['createdAt' => 'DESC']
        );

        // Statistiques du client
        $clientStats = $this->getClientStatistics($email);
        
        // Notifications récentes
        $recentNotifications = $this->notificationRepository->findBy(
            ['recipient' => $email],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('client/dashboard.html.twig', [
            'client_email' => $email,
            'loans' => $loans,
            'stats' => $clientStats,
            'notifications' => $recentNotifications,
        ]);
    }

    #[Route('/loan/{id}', name: 'client_loan_detail')]
    public function loanDetail(Loan $loan, Request $request): Response
    {
        $email = $request->getSession()->get('client_email') ?? $request->query->get('email');
        
        // Vérifier que le prêt appartient au client
        if ($loan->getEmail() !== $email) {
            throw $this->createAccessDeniedException('Accès non autorisé à ce dossier.');
        }

        // Récupérer les documents du prêt
        $documents = $this->mediaRepository->findBy(['loan' => $loan]);
        
        // Récupérer les notifications pour ce prêt
        $notifications = $this->notificationRepository->findByLoan($loan->getId());
        
        // Calculer la progression
        $progress = $this->calculateLoanProgress($loan);
        
        // Documents requis
        $requiredDocuments = $this->getRequiredDocuments($loan);
        $missingDocuments = $this->getMissingDocuments($loan, $documents);

        return $this->render('client/loan_detail.html.twig', [
            'loan' => $loan,
            'documents' => $documents,
            'notifications' => $notifications,
            'progress' => $progress,
            'required_documents' => $requiredDocuments,
            'missing_documents' => $missingDocuments,
        ]);
    }

    #[Route('/upload/{loanId}', name: 'client_upload_documents')]
    public function uploadDocuments(int $loanId, Request $request): Response
    {
        $loan = $this->loanRepository->find($loanId);
        $email = $request->getSession()->get('client_email') ?? $request->query->get('email');
        
        if (!$loan || $loan->getEmail() !== $email) {
            throw $this->createAccessDeniedException('Accès non autorisé.');
        }

        if ($request->isMethod('POST')) {
            // Traitement de l'upload
            $uploadedFile = $request->files->get('document');
            $documentType = $request->request->get('document_type');
            $description = $request->request->get('description');

            if ($uploadedFile) {
                // Créer une nouvelle entité Media
                $media = new \App\Entity\Media();
                $media->setFilename($uploadedFile->getClientOriginalName());
                $media->setMimeType($uploadedFile->getMimeType());
                $media->setFileSize($uploadedFile->getSize());
                $media->setType($documentType);
                $media->setDescription($description);
                $media->setStatus('pending');
                $media->setLoan($loan);

                // Déplacer le fichier
                $uploadDir = $this->getParameter('kernel.project_dir') . '/storage/documents';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $filename = uniqid() . '.' . $uploadedFile->guessExtension();
                $uploadedFile->move($uploadDir, $filename);
                $media->setPath('documents/' . $filename);

                $this->entityManager->persist($media);
                $this->entityManager->flush();

                // Notifier l'administration
                $this->notificationService->notifyLoanEvent($loan, 'documents_received', [
                    'document_type' => $documentType,
                    'description' => $description
                ]);

                $this->addFlash('success', 'Document téléchargé avec succès. Il sera validé sous 24h.');
                
                return $this->redirectToRoute('client_loan_detail', [
                    'id' => $loan->getId(),
                    'email' => $email
                ]);
            }
        }

        $requiredDocuments = $this->getRequiredDocuments($loan);
        $existingDocuments = $this->mediaRepository->findBy(['loan' => $loan]);

        return $this->render('client/upload.html.twig', [
            'loan' => $loan,
            'required_documents' => $requiredDocuments,
            'existing_documents' => $existingDocuments,
        ]);
    }

    #[Route('/simulator', name: 'client_simulator')]
    public function simulator(): Response
    {
        return $this->render('client/simulator.html.twig');
    }

    #[Route('/profile', name: 'client_profile')]
    public function profile(Request $request): Response
    {
        $email = $request->getSession()->get('client_email') ?? $request->query->get('email');
        
        if (!$email) {
            return $this->redirectToRoute('client_dashboard');
        }

        // Récupérer les informations du dernier prêt pour préremplir le profil
        $latestLoan = $this->loanRepository->findOneBy(
            ['email' => $email],
            ['createdAt' => 'DESC']
        );

        return $this->render('client/profile.html.twig', [
            'client_email' => $email,
            'latest_loan' => $latestLoan,
        ]);
    }

    #[Route('/api/loan-progress/{id}', name: 'client_api_loan_progress')]
    public function apiLoanProgress(Loan $loan, Request $request): JsonResponse
    {
        $email = $request->getSession()->get('client_email') ?? $request->query->get('email');
        
        if ($loan->getEmail() !== $email) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $progress = $this->calculateLoanProgress($loan);
        
        return $this->json([
            'progress' => $progress,
            'status' => $loan->getStatus(),
            'status_label' => $loan->getStatusLabel(),
            'last_updated' => $loan->getUpdatedAt()?->format('c') ?? $loan->getCreatedAt()->format('c'),
        ]);
    }

    #[Route('/set-email', name: 'client_set_email', methods: ['POST'])]
    public function setEmail(Request $request): Response
    {
        $email = $request->request->get('email');
        
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $request->getSession()->set('client_email', $email);
            return $this->redirectToRoute('client_dashboard');
        }

        $this->addFlash('error', 'Email invalide.');
        return $this->redirectToRoute('client_dashboard');
    }

    private function getClientStatistics(string $email): array
    {
        $loans = $this->loanRepository->findBy(['email' => $email]);
        
        $stats = [
            'total_loans' => count($loans),
            'pending_loans' => 0,
            'approved_loans' => 0,
            'rejected_loans' => 0,
            'total_amount_requested' => 0,
            'total_amount_approved' => 0,
        ];

        foreach ($loans as $loan) {
            $stats['total_amount_requested'] += (float) $loan->getAmount();
            
            switch ($loan->getStatus()) {
                case 'pending':
                case 'under_review':
                case 'documents_required':
                    $stats['pending_loans']++;
                    break;
                case 'approved':
                    $stats['approved_loans']++;
                    $stats['total_amount_approved'] += (float) $loan->getAmount();
                    break;
                case 'rejected':
                    $stats['rejected_loans']++;
                    break;
            }
        }

        return $stats;
    }

    private function calculateLoanProgress(Loan $loan): array
    {
        $steps = [
            'submitted' => ['label' => 'Demande soumise', 'completed' => true],
            'under_review' => ['label' => 'En cours d\'examen', 'completed' => false],
            'documents' => ['label' => 'Documents validés', 'completed' => false],
            'decision' => ['label' => 'Décision finale', 'completed' => false],
            'finalized' => ['label' => 'Finalisé', 'completed' => false],
        ];

        $currentStep = 0;
        
        switch ($loan->getStatus()) {
            case 'pending':
                $currentStep = 1;
                $steps['under_review']['completed'] = false;
                break;
            case 'under_review':
                $currentStep = 1;
                $steps['under_review']['completed'] = true;
                break;
            case 'documents_required':
                $currentStep = 2;
                $steps['under_review']['completed'] = true;
                break;
            case 'approved':
                $currentStep = 4;
                $steps['under_review']['completed'] = true;
                $steps['documents']['completed'] = true;
                $steps['decision']['completed'] = true;
                break;
            case 'rejected':
                $currentStep = 3;
                $steps['under_review']['completed'] = true;
                $steps['decision']['completed'] = true;
                $steps['decision']['label'] = 'Rejeté';
                break;
            case 'finalized':
                $currentStep = 4;
                foreach ($steps as &$step) {
                    $step['completed'] = true;
                }
                break;
        }

        return [
            'steps' => $steps,
            'current_step' => $currentStep,
            'percentage' => round(($currentStep / 4) * 100),
        ];
    }

    private function getRequiredDocuments(Loan $loan): array
    {
        return [
            'identity' => 'Pièce d\'identité',
            'income' => 'Justificatif de revenus',
            'residence' => 'Justificatif de domicile',
            'bank' => 'RIB',
        ];
    }

    private function getMissingDocuments(Loan $loan, array $documents): array
    {
        $required = $this->getRequiredDocuments($loan);
        $provided = array_map(fn($doc) => $doc->getType(), $documents);
        
        return array_diff_key($required, array_flip($provided));
    }
}
