<?php

namespace App\Controller\Admin;

use App\Entity\Notification;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NotificationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Notification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Notification')
            ->setEntityLabelInPlural('Notifications')
            ->setSearchFields(['subject', 'recipient', 'message', 'event'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25);
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendAction = Action::new('send', 'Envoyer', 'fa fa-paper-plane')
            ->linkToCrudAction('send')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() === 'pending';
            })
            ->setCssClass('btn btn-success');

        $retryAction = Action::new('retry', 'Réessayer', 'fa fa-redo')
            ->linkToCrudAction('retry')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() === 'failed';
            })
            ->setCssClass('btn btn-warning');

        $viewDetailsAction = Action::new('viewDetails', 'Détails', 'fa fa-eye')
            ->linkToCrudAction('viewDetails');

        return $actions
            ->add(Crud::PAGE_INDEX, $sendAction)
            ->add(Crud::PAGE_INDEX, $retryAction)
            ->add(Crud::PAGE_INDEX, $viewDetailsAction)
            ->add(Crud::PAGE_DETAIL, $sendAction)
            ->add(Crud::PAGE_DETAIL, $retryAction);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('type')->setChoices([
                'Email' => 'email',
                'SMS' => 'sms',
                'Notification interne' => 'in_app'
            ]))
            ->add(ChoiceFilter::new('status')->setChoices([
                'En attente' => 'pending',
                'Envoyé' => 'sent',
                'Échec' => 'failed',
                'Livré' => 'delivered'
            ]))
            ->add(ChoiceFilter::new('event')->setChoices([
                'Demande soumise' => 'loan_submitted',
                'Prêt approuvé' => 'loan_approved',
                'Prêt rejeté' => 'loan_rejected',
                'Documents demandés' => 'documents_requested',
                'Documents reçus' => 'documents_received',
                'Rappel de paiement' => 'payment_reminder',
                'Contrat prêt' => 'contract_ready'
            ]))
            ->add(DateTimeFilter::new('createdAt'))
            ->add(DateTimeFilter::new('sentAt'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        
        // Type et canal de communication
        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Email' => 'email',
                'SMS' => 'sms',
                'Notification interne' => 'in_app'
            ])
            ->renderAsBadges([
                'email' => 'primary',
                'sms' => 'info',
                'in_app' => 'secondary'
            ]);
        
        // Événement déclencheur
        yield ChoiceField::new('event', 'Événement')
            ->setChoices([
                'Demande soumise' => 'loan_submitted',
                'Prêt approuvé' => 'loan_approved',
                'Prêt rejeté' => 'loan_rejected',
                'Documents demandés' => 'documents_requested',
                'Documents reçus' => 'documents_received',
                'Rappel de paiement' => 'payment_reminder',
                'Contrat prêt' => 'contract_ready'
            ])
            ->renderAsBadges([
                'loan_submitted' => 'light',
                'loan_approved' => 'success',
                'loan_rejected' => 'danger',
                'documents_requested' => 'warning',
                'documents_received' => 'info',
                'payment_reminder' => 'warning',
                'contract_ready' => 'primary'
            ]);
        
        // Destinataire et contenu
        yield TextField::new('recipient', 'Destinataire');
        yield TextField::new('subject', 'Sujet');
        yield TextareaField::new('message', 'Message')
            ->setMaxLength(500)
            ->hideOnIndex();
        
        // Statut et traitement
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => 'pending',
                'Envoyé' => 'sent',
                'Échec' => 'failed',
                'Livré' => 'delivered'
            ])
            ->renderAsBadges([
                'pending' => 'warning',
                'sent' => 'info',
                'failed' => 'danger',
                'delivered' => 'success'
            ]);
        
        yield NumberField::new('attempts', 'Tentatives')
            ->hideOnForm();
        
        // Relations
        yield AssociationField::new('loan', 'Prêt associé')
            ->hideOnIndex();
        
        // Dates
        yield DateTimeField::new('createdAt', 'Date de création')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
        yield DateTimeField::new('sentAt', 'Date d\'envoi')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
        yield DateTimeField::new('deliveredAt', 'Date de livraison')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    public function send(AdminContext $context): RedirectResponse
    {
        /** @var Notification $notification */
        $notification = $context->getEntity()->getInstance();
        
        try {
            // Simuler l'envoi de la notification
            $this->processNotification($notification);
            
            $notification->setStatus('sent');
            $notification->setSentAt(new \DateTime());
            $notification->setAttempts($notification->getAttempts() + 1);
            
            $this->updateEntity($this->container->get('doctrine')->getManagerForClass(Notification::class), $notification);
            
            $this->addFlash('success', 'Notification envoyée avec succès.');
        } catch (\Exception $e) {
            $notification->setStatus('failed');
            $notification->setAttempts($notification->getAttempts() + 1);
            
            $this->updateEntity($this->container->get('doctrine')->getManagerForClass(Notification::class), $notification);
            
            $this->addFlash('error', 'Échec de l\'envoi de la notification: ' . $e->getMessage());
        }
        
        return $this->redirect($context->getReferrer());
    }

    public function retry(AdminContext $context): RedirectResponse
    {
        /** @var Notification $notification */
        $notification = $context->getEntity()->getInstance();
        $notification->setStatus('pending');
        
        $this->updateEntity($this->container->get('doctrine')->getManagerForClass(Notification::class), $notification);
        
        // Réessayer l'envoi
        return $this->send($context);
    }

    public function viewDetails(AdminContext $context): Response
    {
        /** @var Notification $notification */
        $notification = $context->getEntity()->getInstance();
        
        return $this->render('admin/notification/details.html.twig', [
            'notification' => $notification,
            'metadata' => $notification->getMetadata() ?? []
        ]);
    }

    private function processNotification(Notification $notification): void
    {
        switch ($notification->getType()) {
            case 'email':
                $this->sendEmail($notification);
                break;
            case 'sms':
                $this->sendSms($notification);
                break;
            case 'in_app':
                // Les notifications internes sont déjà créées, marquer comme envoyées
                break;
            default:
                throw new \InvalidArgumentException('Type de notification non supporté: ' . $notification->getType());
        }
    }

    private function sendEmail(Notification $notification): void
    {
        // TODO: Implémenter l'envoi d'email via Symfony Mailer
        // Pour le moment, on simule un envoi réussi
        if (random_int(1, 10) <= 9) {
            // 90% de chance de succès
            return;
        }
        throw new \Exception('Erreur simulée d\'envoi d\'email');
    }

    private function sendSms(Notification $notification): void
    {
        // TODO: Implémenter l'envoi de SMS via un service externe
        // Pour le moment, on simule un envoi réussi
        if (random_int(1, 10) <= 8) {
            // 80% de chance de succès
            return;
        }
        throw new \Exception('Erreur simulée d\'envoi de SMS');
    }
}
