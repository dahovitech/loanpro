<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_profile')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Profil mis à jour avec succès');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    #[Route('/settings', name: 'app_settings')]
    public function settings(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('profile/settings.html.twig', [
            'user' => $user
        ]);
    }
}