<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Loan;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class NotificationService
{
    private array $emailTemplates = [
        'loan_submitted' => [
            'subject' => 'Votre demande de prêt a bien été reçue',
            'template' => 'emails/loan_submitted.html.twig'
        ],
        'loan_approved' => [
            'subject' => 'Félicitations ! Votre prêt a été approuvé',
            'template' => 'emails/loan_approved.html.twig'
        ],
        'loan_rejected' => [
            'subject' => 'Mise à jour concernant votre demande de prêt',
            'template' => 'emails/loan_rejected.html.twig'
        ],
        'documents_requested' => [
            'subject' => 'Documents complémentaires requis',
            'template' => 'emails/documents_requested.html.twig'
        ],
        'documents_received' => [
            'subject' => 'Documents reçus - En cours de traitement',
            'template' => 'emails/documents_received.html.twig'
        ],
        'contract_ready' => [
            'subject' => 'Votre contrat de prêt est prêt',
            'template' => 'emails/contract_ready.html.twig'
        ],
    ];

    private array $smsTemplates = [
        'loan_submitted' => 'LoanPro: Votre demande de prêt a bien été reçue. Référence: {loan_id}',
        'loan_approved' => 'LoanPro: Félicitations ! Votre prêt de {amount}€ a été approuvé. Consultez votre espace client.',
        'loan_rejected' => 'LoanPro: Votre demande de prêt nécessite une révision. Consultez votre espace client.',
        'documents_requested' => 'LoanPro: Documents requis pour votre dossier {loan_id}. Consultez votre espace client.',
        'payment_reminder' => 'LoanPro: Rappel échéance prêt {loan_id} - {amount}€ due le {due_date}',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private LoggerInterface $logger,
        private string $fromEmail = 'noreply@loanpro.com',
        private string $fromName = 'LoanPro'
    ) {}

    /**
     * Create and queue a notification
     */
    public function createNotification(
        string $type,
        string $event,
        string $recipient,
        ?Loan $loan = null,
        array $metadata = []
    ): Notification {
        $notification = new Notification();
        $notification->setType($type);
        $notification->setEvent($event);
        $notification->setRecipient($recipient);
        $notification->setLoan($loan);
        $notification->setMetadata($metadata);

        // Set subject and message based on type and event
        if ($type === 'email') {
            $this->prepareEmailNotification($notification, $loan, $metadata);
        } elseif ($type === 'sms') {
            $this->prepareSmsNotification($notification, $loan, $metadata);
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Send a notification immediately
     */
    public function sendNotification(Notification $notification): bool
    {
        try {
            $notification->incrementAttempts();

            switch ($notification->getType()) {
                case 'email':
                    return $this->sendEmailNotification($notification);
                case 'sms':
                    return $this->sendSmsNotification($notification);
                case 'in_app':
                    return $this->sendInAppNotification($notification);
                default:
                    throw new \InvalidArgumentException('Unknown notification type: ' . $notification->getType());
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to send notification', [
                'notification_id' => $notification->getId(),
                'error' => $e->getMessage()
            ]);

            $notification->markAsFailed($e->getMessage());
            $this->entityManager->flush();
            
            return false;
        }
    }

    /**
     * Process all pending notifications
     */
    public function processPendingNotifications(): int
    {
        $pendingNotifications = $this->notificationRepository->findPending();
        $processed = 0;

        foreach ($pendingNotifications as $notification) {
            if ($this->sendNotification($notification)) {
                $processed++;
            }
        }

        // Also process failed notifications ready for retry
        $retryNotifications = $this->notificationRepository->findPendingForRetry();
        foreach ($retryNotifications as $notification) {
            if ($this->sendNotification($notification)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Send email notification to loan applicant
     */
    public function notifyLoanEvent(Loan $loan, string $event, array $additionalData = []): void
    {
        // Prepare metadata for template
        $metadata = array_merge([
            'loan_id' => $loan->getId(),
            'first_name' => $loan->getFirstName(),
            'last_name' => $loan->getLastName(),
            'amount' => $loan->getAmount(),
            'duration' => $loan->getDuration(),
            'monthly_payment' => $loan->getMonthlyPayment(),
            'status' => $loan->getStatus(),
            'created_at' => $loan->getCreatedAt()->format('d/m/Y'),
        ], $additionalData);

        // Send email notification
        $this->createNotification('email', $event, $loan->getEmail(), $loan, $metadata);

        // Send SMS notification if phone is available
        if ($loan->getPhone()) {
            $this->createNotification('sms', $event, $loan->getPhone(), $loan, $metadata);
        }
    }

    /**
     * Send bulk notifications (for admin communications)
     */
    public function sendBulkNotification(
        array $recipients,
        string $subject,
        string $message,
        string $type = 'email'
    ): int {
        $sent = 0;
        
        foreach ($recipients as $recipient) {
            $notification = new Notification();
            $notification->setType($type);
            $notification->setEvent('bulk_communication');
            $notification->setRecipient($recipient);
            $notification->setSubject($subject);
            $notification->setMessage($message);

            $this->entityManager->persist($notification);
            
            if ($this->sendNotification($notification)) {
                $sent++;
            }
        }

        $this->entityManager->flush();
        return $sent;
    }

    private function prepareEmailNotification(Notification $notification, ?Loan $loan, array $metadata): void
    {
        $event = $notification->getEvent();
        
        if (!isset($this->emailTemplates[$event])) {
            throw new \InvalidArgumentException('Unknown email event: ' . $event);
        }

        $template = $this->emailTemplates[$event];
        $notification->setSubject($template['subject']);

        // Render email content from template
        try {
            $htmlContent = $this->twig->render($template['template'], [
                'loan' => $loan,
                'metadata' => $metadata,
                'notification' => $notification
            ]);
            $notification->setMessage($htmlContent);
        } catch (\Exception $e) {
            // Fallback to simple text if template fails
            $notification->setMessage($this->generateFallbackEmailContent($event, $loan, $metadata));
        }
    }

    private function prepareSmsNotification(Notification $notification, ?Loan $loan, array $metadata): void
    {
        $event = $notification->getEvent();
        
        if (!isset($this->smsTemplates[$event])) {
            throw new \InvalidArgumentException('Unknown SMS event: ' . $event);
        }

        $template = $this->smsTemplates[$event];
        $message = $this->replacePlaceholders($template, $loan, $metadata);
        
        $notification->setSubject($event); // For SMS, subject is the event type
        $notification->setMessage($message);
    }

    private function sendEmailNotification(Notification $notification): bool
    {
        $email = (new Email())
            ->from($this->fromEmail, $this->fromName)
            ->to($notification->getRecipient())
            ->subject($notification->getSubject())
            ->html($notification->getMessage());

        $this->mailer->send($email);
        
        $notification->markAsSent();
        $this->entityManager->flush();
        
        return true;
    }

    private function sendSmsNotification(Notification $notification): bool
    {
        // TODO: Implement SMS sending with your SMS provider (Twilio, etc.)
        // For now, we'll simulate SMS sending
        
        $this->logger->info('SMS would be sent', [
            'recipient' => $notification->getRecipient(),
            'message' => $notification->getMessage()
        ]);

        // Simulate SMS delivery
        $notification->markAsSent();
        
        // Simulate delivery confirmation after a short delay
        $notification->markAsDelivered();
        
        $this->entityManager->flush();
        
        return true;
    }

    private function sendInAppNotification(Notification $notification): bool
    {
        // TODO: Implement in-app notification system
        // This could store notifications in database for display in user dashboard
        
        $notification->markAsDelivered();
        $this->entityManager->flush();
        
        return true;
    }

    private function replacePlaceholders(string $template, ?Loan $loan, array $metadata): string
    {
        $placeholders = [];
        
        if ($loan) {
            $placeholders['{loan_id}'] = $loan->getId();
            $placeholders['{first_name}'] = $loan->getFirstName();
            $placeholders['{last_name}'] = $loan->getLastName();
            $placeholders['{amount}'] = number_format((float)$loan->getAmount(), 0, ',', ' ');
            $placeholders['{duration}'] = $loan->getDuration();
        }

        foreach ($metadata as $key => $value) {
            $placeholders['{' . $key . '}'] = $value;
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    private function generateFallbackEmailContent(string $event, ?Loan $loan, array $metadata): string
    {
        $content = "<h2>LoanPro - " . $this->emailTemplates[$event]['subject'] . "</h2>";
        
        if ($loan) {
            $content .= "<p>Bonjour " . $loan->getFirstName() . " " . $loan->getLastName() . ",</p>";
            $content .= "<p>Concernant votre demande de prêt n°" . $loan->getId() . " de " . number_format((float)$loan->getAmount()) . "€.</p>";
        }

        $content .= "<p>Cordialement,<br>L'équipe LoanPro</p>";
        
        return $content;
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(): array
    {
        return $this->notificationRepository->getStatistics();
    }

    /**
     * Clean old notifications
     */
    public function cleanOldNotifications(int $daysOld = 90): int
    {
        return $this->notificationRepository->cleanOldNotifications($daysOld);
    }

    /**
     * Send admin alert for system events
     */
    public function sendAdminAlert(string $subject, string $message, string $adminEmail = 'admin@loanpro.com'): void
    {
        $notification = new Notification();
        $notification->setType('email');
        $notification->setEvent('admin_alert');
        $notification->setRecipient($adminEmail);
        $notification->setSubject('[ADMIN ALERT] ' . $subject);
        $notification->setMessage($message);

        $this->entityManager->persist($notification);
        $this->sendNotification($notification);
    }
}
