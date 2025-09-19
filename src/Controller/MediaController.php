<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Entity\Media;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/media')]
class MediaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MediaRepository $mediaRepository
    ) {}

    #[Route('/upload/{loanId}', name: 'app_media_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, int $loanId): Response
    {
        $loan = $this->entityManager->getRepository(Loan::class)->find($loanId);
        
        if (!$loan) {
            throw $this->createNotFoundException('Demande de prêt non trouvée');
        }

        $media = new Media();
        $form = $this->createForm(MediaType::class, $media);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($media);
            $loan->addDocument($media);
            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Document uploadé avec succès',
                    'mediaId' => $media->getId(),
                    'fileName' => $media->getOriginalFileName(),
                    'type' => $media->getTypeLabel()
                ]);
            }

            $this->addFlash('success', 'Document ajouté avec succès');
            return $this->redirectToRoute('app_loan_status', ['id' => $loan->getId()]);
        }

        return $this->render('media/upload.html.twig', [
            'form' => $form->createView(),
            'loan' => $loan,
        ]);
    }

    #[Route('/view/{id}', name: 'app_media_view', methods: ['GET'])]
    public function view(Media $media): Response
    {
        if (!$media->exists()) {
            throw $this->createNotFoundException('Fichier non trouvé');
        }

        $filePath = $media->getAbsolutePath();
        
        if ($media->isImage()) {
            return new BinaryFileResponse($filePath, 200, [
                'Content-Type' => $media->getMimeType() ?: 'application/octet-stream'
            ]);
        }

        return $this->render('media/view.html.twig', [
            'media' => $media,
        ]);
    }

    #[Route('/download/{id}', name: 'app_media_download', methods: ['GET'])]
    public function download(Media $media): Response
    {
        if (!$media->exists()) {
            throw $this->createNotFoundException('Fichier non trouvé');
        }

        $response = new BinaryFileResponse($media->getAbsolutePath());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $media->getOriginalFileName() ?: $media->getFileName()
        );

        return $response;
    }

    #[Route('/delete/{id}', name: 'app_media_delete', methods: ['POST'])]
    public function delete(Request $request, Media $media): Response
    {
        if ($this->isCsrfTokenValid('delete'.$media->getId(), $request->request->get('_token'))) {
            $loanId = null;
            
            // Récupérer l'ID du prêt pour la redirection
            if (!$media->getLoans()->isEmpty()) {
                $loanId = $media->getLoans()->first()->getId();
            }

            $this->entityManager->remove($media);
            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Document supprimé avec succès'
                ]);
            }

            $this->addFlash('success', 'Document supprimé avec succès');
            
            if ($loanId) {
                return $this->redirectToRoute('app_loan_status', ['id' => $loanId]);
            }
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/validate/{id}', name: 'app_media_validate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function validate(Request $request, Media $media): Response
    {
        if ($this->isCsrfTokenValid('validate'.$media->getId(), $request->request->get('_token'))) {
            $media->setIsValidated(true);
            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Document validé avec succès'
                ]);
            }

            $this->addFlash('success', 'Document validé avec succès');
        }

        return $this->redirectToRoute('admin_loan_show', ['id' => $request->query->get('loanId')]);
    }

    #[Route('/ajax/upload', name: 'app_media_ajax_upload', methods: ['POST'])]
    public function ajaxUpload(Request $request): JsonResponse
    {
        try {
            $uploadedFile = $request->files->get('file');
            $loanId = $request->request->get('loanId');
            $type = $request->request->get('type');

            if (!$uploadedFile) {
                return new JsonResponse(['success' => false, 'message' => 'Aucun fichier uploadé'], 400);
            }

            if (!$loanId) {
                return new JsonResponse(['success' => false, 'message' => 'ID de prêt manquant'], 400);
            }

            $loan = $this->entityManager->getRepository(Loan::class)->find($loanId);
            if (!$loan) {
                return new JsonResponse(['success' => false, 'message' => 'Prêt non trouvé'], 404);
            }

            $media = new Media();
            $media->setFile($uploadedFile);
            $media->setType($type ?: Media::TYPE_OTHER);
            
            $this->entityManager->persist($media);
            $loan->addDocument($media);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'media' => [
                    'id' => $media->getId(),
                    'fileName' => $media->getOriginalFileName(),
                    'type' => $media->getTypeLabel(),
                    'size' => $media->getFileSizeFormatted(),
                    'downloadUrl' => $this->generateUrl('app_media_download', ['id' => $media->getId()]),
                    'deleteUrl' => $this->generateUrl('app_media_delete', ['id' => $media->getId()]),
                    'iconClass' => $media->getIconClass()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/list/{loanId}', name: 'app_media_list', methods: ['GET'])]
    public function list(int $loanId): JsonResponse
    {
        $loan = $this->entityManager->getRepository(Loan::class)->find($loanId);
        
        if (!$loan) {
            return new JsonResponse(['success' => false, 'message' => 'Prêt non trouvé'], 404);
        }

        $documents = [];
        foreach ($loan->getDocuments() as $media) {
            $documents[] = [
                'id' => $media->getId(),
                'fileName' => $media->getOriginalFileName(),
                'type' => $media->getType(),
                'typeLabel' => $media->getTypeLabel(),
                'size' => $media->getFileSizeFormatted(),
                'isValidated' => $media->isValidated(),
                'createdAt' => $media->getCreatedAt()->format('d/m/Y H:i'),
                'downloadUrl' => $this->generateUrl('app_media_download', ['id' => $media->getId()]),
                'viewUrl' => $this->generateUrl('app_media_view', ['id' => $media->getId()]),
                'deleteUrl' => $this->generateUrl('app_media_delete', ['id' => $media->getId()]),
                'iconClass' => $media->getIconClass()
            ];
        }

        return new JsonResponse([
            'success' => true,
            'documents' => $documents,
            'requiredDocuments' => $loan->getRequiredDocuments(),
            'missingDocuments' => $loan->getMissingRequiredDocuments()
        ]);
    }
}
