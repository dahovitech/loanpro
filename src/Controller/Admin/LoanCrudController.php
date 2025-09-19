<?php

namespace App\Controller\Admin;

use App\Entity\Loan;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LoanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Loan::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de prêt')
            ->setEntityLabelInPlural('Demandes de prêts')
            ->setSearchFields(['firstName', 'lastName', 'email', 'phone', 'status'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25);
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveAction = Action::new('approve', 'Approuver', 'fa fa-check')
            ->linkToCrudAction('approve')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() === 'pending';
            })
            ->setCssClass('btn btn-success');

        $rejectAction = Action::new('reject', 'Rejeter', 'fa fa-times')
            ->linkToCrudAction('reject')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() === 'pending';
            })
            ->setCssClass('btn btn-danger');

        $viewDocuments = Action::new('viewDocuments', 'Documents', 'fa fa-file')
            ->linkToCrudAction('viewDocuments');

        return $actions
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $viewDocuments)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $viewDocuments);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'En attente' => 'pending',
                'Approuvé' => 'approved',
                'Rejeté' => 'rejected',
                'En cours' => 'in_progress',
                'Complété' => 'completed'
            ]))
            ->add(DateTimeFilter::new('createdAt'))
            ->add('amount')
            ->add('duration');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        
        // Informations personnelles
        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield EmailField::new('email', 'Email');
        yield TextField::new('phone', 'Téléphone');
        yield TextField::new('address', 'Adresse')->hideOnIndex();
        yield TextField::new('profession', 'Profession')->hideOnIndex();
        
        // Détails du prêt
        yield NumberField::new('amount', 'Montant (€)')
            ->setNumDecimals(2)
            ->setStoredAsString(false);
        yield NumberField::new('duration', 'Durée (mois)');
        yield NumberField::new('monthlyPayment', 'Mensualité (€)')
            ->setNumDecimals(2)
            ->setStoredAsString(false)
            ->hideOnForm();
        yield NumberField::new('totalCost', 'Coût total (€)')
            ->setNumDecimals(2)
            ->setStoredAsString(false)
            ->hideOnForm();
        
        // Statut et workflow
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => 'pending',
                'Approuvé' => 'approved',
                'Rejeté' => 'rejected',
                'En cours' => 'in_progress',
                'Complété' => 'completed'
            ])
            ->renderAsBadges([
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
                'in_progress' => 'info',
                'completed' => 'primary'
            ]);
        
        // Documents et relations
        yield AssociationField::new('medias', 'Documents')
            ->hideOnIndex()
            ->formatValue(function ($value, $entity) {
                return count($entity->getMedias()) . ' document(s)';
            });
        
        yield AssociationField::new('user', 'Utilisateur')->hideOnForm();
        
        // Notes administratives
        yield TextEditorField::new('adminNotes', 'Notes administratives')
            ->hideOnIndex()
            ->setRequired(false);
        
        // Dates
        yield DateTimeField::new('createdAt', 'Date de création')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
        yield DateTimeField::new('updatedAt', 'Dernière modification')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    public function approve(AdminContext $context): RedirectResponse
    {
        /** @var Loan $loan */
        $loan = $context->getEntity()->getInstance();
        $loan->setStatus('approved');
        $loan->setUpdatedAt(new \DateTime());
        
        $this->updateEntity($this->container->get('doctrine')->getManagerForClass(Loan::class), $loan);
        
        $this->addFlash('success', 'Demande de prêt approuvée avec succès.');
        
        return $this->redirect($context->getReferrer());
    }

    public function reject(AdminContext $context): RedirectResponse
    {
        /** @var Loan $loan */
        $loan = $context->getEntity()->getInstance();
        $loan->setStatus('rejected');
        $loan->setUpdatedAt(new \DateTime());
        
        $this->updateEntity($this->container->get('doctrine')->getManagerForClass(Loan::class), $loan);
        
        $this->addFlash('warning', 'Demande de prêt rejetée.');
        
        return $this->redirect($context->getReferrer());
    }

    public function viewDocuments(AdminContext $context): RedirectResponse
    {
        /** @var Loan $loan */
        $loan = $context->getEntity()->getInstance();
        
        // Redirection vers la gestion des documents pour ce prêt
        $adminUrlGenerator = $this->container->get('EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator');
        $url = $adminUrlGenerator
            ->setController(MediaCrudController::class)
            ->setAction('index')
            ->set('filters[loans]', $loan->getId())
            ->generateUrl();
        
        return $this->redirect($url);
    }
}
