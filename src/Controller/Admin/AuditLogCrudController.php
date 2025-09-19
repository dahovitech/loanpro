<?php

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use App\Security\Voter\AdminVoter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(AdminVoter::ADMIN_REPORTS)]
class AuditLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AuditLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Log d\'audit')
            ->setEntityLabelInPlural('Logs d\'audit')
            ->setSearchFields(['action', 'entityType', 'description', 'user.email'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE) // Lecture seule
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('action')->setChoices([
                'Création' => 'create',
                'Modification' => 'update',
                'Suppression' => 'delete',
                'Approbation' => 'approve',
                'Rejet' => 'reject',
                'Activation' => 'activate',
                'Désactivation' => 'deactivate',
                'Envoi' => 'send',
                'Upload' => 'upload',
                'Téléchargement' => 'download',
                'Connexion' => 'login',
                'Déconnexion' => 'logout',
            ]))
            ->add(ChoiceFilter::new('entityType')->setChoices([
                'Prêt' => 'Loan',
                'Utilisateur' => 'User',
                'Document' => 'Media',
                'Notification' => 'Notification',
            ]))
            ->add(DateTimeFilter::new('createdAt'))
            ->add('user')
            ->add('ipAddress');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        
        // Action et type
        yield ChoiceField::new('action', 'Action')
            ->setChoices([
                'Création' => 'create',
                'Modification' => 'update',
                'Suppression' => 'delete',
                'Approbation' => 'approve',
                'Rejet' => 'reject',
                'Activation' => 'activate',
                'Désactivation' => 'deactivate',
                'Envoi' => 'send',
                'Upload' => 'upload',
                'Téléchargement' => 'download',
                'Connexion' => 'login',
                'Déconnexion' => 'logout',
            ])
            ->renderAsBadges([
                'create' => 'success',
                'update' => 'info',
                'delete' => 'danger',
                'approve' => 'success',
                'reject' => 'danger',
                'activate' => 'success',
                'deactivate' => 'warning',
                'send' => 'primary',
                'upload' => 'info',
                'download' => 'secondary',
                'login' => 'primary',
                'logout' => 'secondary',
            ])
            ->hideOnForm();
        
        // Entité concernée
        yield TextField::new('entityType', 'Type d\'entité')->hideOnForm();
        yield TextField::new('entityId', 'ID Entité')->hideOnIndex()->hideOnForm();
        
        // Utilisateur
        yield AssociationField::new('user', 'Utilisateur')
            ->formatValue(function ($value, $entity) {
                if ($entity->getUser()) {
                    return $entity->getUser()->getFirstName() . ' ' . $entity->getUser()->getLastName() . ' (' . $entity->getUser()->getEmail() . ')';
                }
                return 'Système';
            })
            ->hideOnForm();
        
        // Description
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->hideOnForm();
        
        // Valeurs pour le détail uniquement
        if ($pageName === Crud::PAGE_DETAIL) {
            yield CodeEditorField::new('oldValues', 'Anciennes valeurs')
                ->setLanguage('json')
                ->formatValue(function ($value) {
                    return $value ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null;
                })
                ->hideOnIndex()
                ->hideOnForm();
            
            yield CodeEditorField::new('newValues', 'Nouvelles valeurs')
                ->setLanguage('json')
                ->formatValue(function ($value) {
                    return $value ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null;
                })
                ->hideOnIndex()
                ->hideOnForm();
        }
        
        // Informations techniques
        yield TextField::new('ipAddress', 'Adresse IP')->hideOnIndex()->hideOnForm();
        yield TextareaField::new('userAgent', 'User Agent')->hideOnIndex()->hideOnForm();
        
        // Date
        yield DateTimeField::new('createdAt', 'Date')
            ->setFormat('dd/MM/yyyy HH:mm:ss')
            ->hideOnForm();
    }
}
