<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Form\LoanType;
use App\Repository\LoanRepository;
use App\Service\ConfigService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoanController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoanRepository $loanRepository,
        private ConfigService $configService,
        private NotificationService $notificationService
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $locale = $request->getLocale();
        $config = $this->configService->getConfig($locale);

        return $this->render('loan/index.html.twig', [
            'config' => $config,
        ]);
    }

    #[Route('/apply', name: 'app_loan_apply')]
    public function apply(Request $request): Response
    {
        $loan = new Loan();
        $form = $this->createForm(LoanType::class, $loan);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($loan);
            $this->entityManager->flush();

            // Envoyer notification de confirmation
            $this->notificationService->notifyLoanEvent($loan, 'loan_submitted');

            $this->addFlash('success', 'Votre demande de prêt a été soumise avec succès. Nous vous recontacterons bientôt.');
            
            return $this->redirectToRoute('app_loan_status', ['id' => $loan->getId()]);
        }

        $locale = $request->getLocale();
        $config = $this->configService->getConfig($locale);

        return $this->render('loan/apply.html.twig', [
            'form' => $form->createView(),
            'config' => $config,
        ]);
    }

    #[Route('/status/{id}', name: 'app_loan_status')]
    public function status(Loan $loan, Request $request): Response
    {
        $locale = $request->getLocale();
        $config = $this->configService->getConfig($locale);

        return $this->render('loan/status.html.twig', [
            'loan' => $loan,
            'config' => $config,
        ]);
    }

    #[Route('/calculator', name: 'app_loan_calculator')]
    public function calculator(Request $request): Response
    {
        $locale = $request->getLocale();
        $config = $this->configService->getConfig($locale);

        return $this->render('loan/calculator.html.twig', [
            'config' => $config,
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(Request $request): Response
    {
        $locale = $request->getLocale();
        $config = $this->configService->getConfig($locale);

        return $this->render('loan/about.html.twig', [
            'config' => $config,
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request): Response
    {
        $locale = $request->getLocale();
        $config = $this->configService->getConfig($locale);

        return $this->render('loan/contact.html.twig', [
            'config' => $config,
        ]);
    }
}
