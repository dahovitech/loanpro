<?php

namespace App\Controller;

use App\Form\PasswordResetRequestType;
use App\Form\PasswordResetType;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordResetController extends AbstractController
{
    public function __construct(
        private PasswordResetService $passwordResetService,
        private TranslatorInterface $translator
    ) {
    }

    #[Route('/password/forgot', name: 'app_password_forgot')]
    public function requestReset(Request $request): Response
    {
        // Redirect authenticated users
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        $form = $this->createForm(PasswordResetRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            
            $success = $this->passwordResetService->sendPasswordResetEmail($email);
            
            if ($success) {
                $this->addFlash('success', $this->translator->trans('password_reset.request.success'));
            } else {
                $this->addFlash('error', $this->translator->trans('password_reset.request.error'));
            }

            // Always redirect to login page for security (don't reveal if email exists)
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_reset_request.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/password/reset/{token}', name: 'app_password_reset')]
    public function resetPassword(Request $request, string $token): Response
    {
        // Redirect authenticated users
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        // Validate the token
        $resetToken = $this->passwordResetService->validateToken($token);
        
        if (!$resetToken || !$resetToken->isValid()) {
            $this->addFlash('error', $this->translator->trans('password_reset.token.invalid'));
            return $this->redirectToRoute('app_password_forgot');
        }

        // Check if token is expired
        if ($resetToken->isExpired()) {
            $this->addFlash('error', $this->translator->trans('password_reset.token.expired'));
            return $this->redirectToRoute('app_password_forgot');
        }

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            
            $success = $this->passwordResetService->resetPassword($token, $newPassword);
            
            if ($success) {
                $this->addFlash('success', $this->translator->trans('password_reset.success'));
                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', $this->translator->trans('password_reset.error'));
            }
        }

        return $this->render('security/password_reset.html.twig', [
            'form' => $form,
            'token' => $token,
            'user' => $resetToken->getUser(),
            'expires_at' => $resetToken->getExpiresAt(),
        ]);
    }

    #[Route('/password/check-token/{token}', name: 'app_password_check_token')]
    public function checkToken(string $token): Response
    {
        $resetToken = $this->passwordResetService->validateToken($token);
        
        if (!$resetToken || !$resetToken->isValid()) {
            $this->addFlash('error', $this->translator->trans('password_reset.token.invalid'));
            return $this->redirectToRoute('app_password_forgot');
        }

        return $this->redirectToRoute('app_password_reset', ['token' => $token]);
    }
}