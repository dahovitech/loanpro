<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setSearchFields(['firstName', 'lastName', 'email', 'phone'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25);
    }

    public function configureActions(Actions $actions): Actions
    {
        $activateAction = Action::new('activate', 'Activer', 'fa fa-check-circle')
            ->linkToCrudAction('activate')
            ->displayIf(static function ($entity) {
                return !$entity->isVerified();
            })
            ->setCssClass('btn btn-success');

        $deactivateAction = Action::new('deactivate', 'Désactiver', 'fa fa-ban')
            ->linkToCrudAction('deactivate')
            ->displayIf(static function ($entity) {
                return $entity->isVerified();
            })
            ->setCssClass('btn btn-warning');

        $resetPasswordAction = Action::new('resetPassword', 'Réinitialiser mot de passe', 'fa fa-key')
            ->linkToCrudAction('resetPassword')
            ->setCssClass('btn btn-info');

        $viewLoansAction = Action::new('viewLoans', 'Voir les prêts', 'fa fa-money-bill')
            ->linkToCrudAction('viewLoans');

        return $actions
            ->add(Crud::PAGE_INDEX, $activateAction)
            ->add(Crud::PAGE_INDEX, $deactivateAction)
            ->add(Crud::PAGE_INDEX, $resetPasswordAction)
            ->add(Crud::PAGE_INDEX, $viewLoansAction)
            ->add(Crud::PAGE_DETAIL, $activateAction)
            ->add(Crud::PAGE_DETAIL, $deactivateAction)
            ->add(Crud::PAGE_DETAIL, $resetPasswordAction)
            ->add(Crud::PAGE_DETAIL, $viewLoansAction);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('roles')->setChoices([
                'Utilisateur' => 'ROLE_USER',
                'Administrateur' => 'ROLE_ADMIN',
                'Super Admin' => 'ROLE_SUPER_ADMIN'
            ]))
            ->add(BooleanFilter::new('isVerified'))
            ->add(DateTimeFilter::new('createdAt'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        
        // Informations personnelles
        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield EmailField::new('email', 'Email');
        yield TextField::new('phone', 'Téléphone')->hideOnIndex();
        
        // Authentification et sécurité
        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            yield TextField::new('plainPassword', 'Mot de passe')
                ->setRequired($pageName === Crud::PAGE_NEW)
                ->hideOnIndex()
                ->setHelp('Laissez vide pour ne pas modifier le mot de passe existant');
        }
        
        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Utilisateur' => 'ROLE_USER',
                'Administrateur' => 'ROLE_ADMIN',
                'Super Admin' => 'ROLE_SUPER_ADMIN'
            ])
            ->allowMultipleChoices()
            ->renderAsBadges([
                'ROLE_USER' => 'primary',
                'ROLE_ADMIN' => 'success',
                'ROLE_SUPER_ADMIN' => 'danger'
            ]);
        
        yield BooleanField::new('isVerified', 'Compte vérifié')
            ->renderAsSwitch(false);
        
        // Relations et statistiques
        yield AssociationField::new('loans', 'Demandes de prêt')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                $loans = $entity->getLoans();
                $count = count($loans);
                if ($count === 0) {
                    return 'Aucune demande';
                }
                return $count . ' demande(s)';
            });
        
        // Dates
        yield DateTimeField::new('createdAt', 'Date d\'inscription')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
        yield DateTimeField::new('updatedAt', 'Dernière modification')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user = $entityInstance;
        
        if ($user->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPlainPassword());
            $user->setPassword($hashedPassword);
        }
        
        if (!$user->getRoles()) {
            $user->setRoles(['ROLE_USER']);
        }
        
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user = $entityInstance;
        
        if ($user->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPlainPassword());
            $user->setPassword($hashedPassword);
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function activate(AdminContext $context): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        $user->setIsVerified(true);
        $user->setUpdatedAt(new \DateTime());
        
        $this->updateEntity($this->container->get('doctrine')->getManagerForClass(User::class), $user);
        
        $this->addFlash('success', 'Utilisateur activé avec succès.');
        
        return $this->redirect($context->getReferrer());
    }

    public function deactivate(AdminContext $context): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        $user->setIsVerified(false);
        $user->setUpdatedAt(new \DateTime());
        
        $this->updateEntity($this->container->get('doctrine')->getManagerForClass(User::class), $user);
        
        $this->addFlash('warning', 'Utilisateur désactivé.');
        
        return $this->redirect($context->getReferrer());
    }

    public function resetPassword(AdminContext $context): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        
        // Générer un mot de passe temporaire
        $tempPassword = bin2hex(random_bytes(8));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
        $user->setPassword($hashedPassword);
        $user->setUpdatedAt(new \DateTime());
        
        $this->updateEntity($this->container->get('doctrine')->getManagerForClass(User::class), $user);
        
        // TODO: Envoyer le nouveau mot de passe par email
        $this->addFlash('success', 'Mot de passe réinitialisé. Nouveau mot de passe temporaire: ' . $tempPassword);
        
        return $this->redirect($context->getReferrer());
    }

    public function viewLoans(AdminContext $context): RedirectResponse
    {
        /** @var User $user */
        $user = $context->getEntity()->getInstance();
        
        // Redirection vers les prêts de cet utilisateur
        $adminUrlGenerator = $this->container->get('EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator');
        $url = $adminUrlGenerator
            ->setController(LoanCrudController::class)
            ->setAction('index')
            ->set('filters[user]', $user->getId())
            ->generateUrl();
        
        return $this->redirect($url);
    }
}
