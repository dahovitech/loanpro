<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class PasswordResetService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private PasswordResetTokenRepository $tokenRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Generate and send a password reset email
     */
    public function sendPasswordResetEmail(string $email): bool
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            // For security reasons, we don't reveal that the email doesn't exist
            return true;
        }

        if (!$user->getIsActive()) {
            // Don't send reset emails for inactive users
            return false;
        }

        // Check if user has requested too many tokens recently (prevent spam)
        $recentTokens = $this->tokenRepository->countRecentTokensForUser($user, 15);
        if ($recentTokens >= 3) {
            return false;
        }

        // Invalidate any existing valid tokens for this user
        $this->tokenRepository->invalidateAllTokensForUser($user);
        $this->entityManager->flush();

        // Create new token
        $token = new PasswordResetToken();
        $token->setUser($user);
        
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        // Send email
        try {
            $this->sendResetEmail($user, $token);
            return true;
        } catch (\Exception $e) {
            // Log the error in a real application
            return false;
        }
    }

    /**
     * Reset password using a token
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $resetToken = $this->tokenRepository->findValidToken($token);
        
        if (!$resetToken || !$resetToken->isValid()) {
            return false;
        }

        $user = $resetToken->getUser();
        
        // Hash and set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setUpdatedAt(new \DateTime());

        // Mark token as used
        $resetToken->markAsUsed();

        // Invalidate all other tokens for this user
        $this->tokenRepository->invalidateAllTokensForUser($user);

        $this->entityManager->flush();

        return true;
    }

    /**
     * Validate a reset token
     */
    public function validateToken(string $token): ?PasswordResetToken
    {
        return $this->tokenRepository->findValidToken($token);
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return $this->tokenRepository->removeExpiredTokens();
    }

    /**
     * Send the password reset email
     */
    private function sendResetEmail(User $user, PasswordResetToken $token): void
    {
        $resetUrl = $this->urlGenerator->generate(
            'app_password_reset',
            ['token' => $token->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('noreply@loanpro.com')
            ->to($user->getEmail())
            ->subject($this->translator->trans('password_reset.email.subject'))
            ->html($this->twig->render('emails/password_reset.html.twig', [
                'user' => $user,
                'reset_url' => $resetUrl,
                'token' => $token,
                'expires_at' => $token->getExpiresAt()
            ]));

        $this->mailer->send($email);
    }
}