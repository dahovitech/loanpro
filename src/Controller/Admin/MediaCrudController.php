<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;

class MediaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Media::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Document')
            ->setEntityLabelInPlural('Documents')
            ->setSearchFields(['filename', 'originalName', 'type'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25);
    }

    public function configureActions(Actions $actions): Actions
    {
        $downloadAction = Action::new('download', 'Télécharger', 'fa fa-download')
            ->linkToCrudAction('download');

        $previewAction = Action::new('preview', 'Aperçu', 'fa fa-eye')
            ->linkToCrudAction('preview')
            ->displayIf(static function ($entity) {
                return in_array($entity->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $downloadAction)
            ->add(Crud::PAGE_INDEX, $previewAction)
            ->add(Crud::PAGE_DETAIL, $downloadAction)
            ->add(Crud::PAGE_DETAIL, $previewAction)
            ->disable(Action::NEW) // Les documents sont uploadés via le frontend
            ->disable(Action::EDIT); // Les documents ne sont pas modifiables
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('type')->setChoices([
                'Pièce d\'identité' => 'identity',
                'Justificatif de revenus' => 'income',
                'Justificatif de domicile' => 'address',
                'Relevé bancaire' => 'bank_statement',
                'Contrat de travail' => 'employment_contract',
                'Autres' => 'other'
            ]))
            ->add(DateTimeFilter::new('createdAt'))
            ->add('mimeType')
            ->add('size');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        
        // Informations du fichier
        yield TextField::new('originalName', 'Nom original');
        yield TextField::new('filename', 'Nom du fichier')->hideOnIndex();
        
        // Type et catégorisation
        yield ChoiceField::new('type', 'Type de document')
            ->setChoices([
                'Pièce d\'identité' => 'identity',
                'Justificatif de revenus' => 'income',
                'Justificatif de domicile' => 'address',
                'Relevé bancaire' => 'bank_statement',
                'Contrat de travail' => 'employment_contract',
                'Autres' => 'other'
            ])
            ->renderAsBadges([
                'identity' => 'primary',
                'income' => 'success',
                'address' => 'info',
                'bank_statement' => 'warning',
                'employment_contract' => 'secondary',
                'other' => 'light'
            ]);
        
        // Métadonnées techniques
        yield TextField::new('mimeType', 'Type MIME')->hideOnIndex();
        yield NumberField::new('size', 'Taille (octets)')
            ->formatValue(function ($value) {
                return $this->formatFileSize($value);
            })
            ->hideOnForm();
        
        // Aperçu pour les images
        if ($pageName === Crud::PAGE_DETAIL || $pageName === Crud::PAGE_INDEX) {
            yield ImageField::new('path', 'Aperçu')
                ->setBasePath('/uploads/media/')
                ->setUploadDir('public/uploads/media')
                ->hideOnIndex()
                ->formatValue(function ($value, $entity) {
                    if (strpos($entity->getMimeType(), 'image/') === 0) {
                        return $entity->getPath();
                    }
                    return null;
                });
        }
        
        // Relations
        yield AssociationField::new('loans', 'Prêts associés')
            ->formatValue(function ($value, $entity) {
                $loans = $entity->getLoans();
                $count = count($loans);
                if ($count === 0) {
                    return 'Aucun prêt associé';
                }
                return $count . ' prêt(s) associé(s)';
            });
        
        // Dates
        yield DateTimeField::new('createdAt', 'Date d\'upload')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
        yield DateTimeField::new('updatedAt', 'Dernière modification')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function download(AdminContext $context): Response
    {
        /** @var Media $media */
        $media = $context->getEntity()->getInstance();
        
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/media/' . $media->getPath();
        
        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier n\'existe plus sur le serveur.');
            return $this->redirect($context->getReferrer());
        }
        
        return $this->file($filePath, $media->getOriginalName());
    }

    public function preview(AdminContext $context): Response
    {
        /** @var Media $media */
        $media = $context->getEntity()->getInstance();
        
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/media/' . $media->getPath();
        
        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier n\'existe plus sur le serveur.');
            return $this->redirect($context->getReferrer());
        }
        
        // Pour les PDFs, on peut implémenter un viewer
        if ($media->getMimeType() === 'application/pdf') {
            return $this->render('admin/media/preview_pdf.html.twig', [
                'media' => $media,
                'file_url' => '/uploads/media/' . $media->getPath()
            ]);
        }
        
        // Pour les images, affichage direct
        if (strpos($media->getMimeType(), 'image/') === 0) {
            return $this->render('admin/media/preview_image.html.twig', [
                'media' => $media,
                'file_url' => '/uploads/media/' . $media->getPath()
            ]);
        }
        
        // Pour les autres types, redirection vers téléchargement
        return $this->download($context);
    }
}
