<?php

namespace App\Command;

use App\Service\PasswordResetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:password-reset:cleanup',
    description: 'Clean up expired password reset tokens',
)]
class PasswordResetCleanupCommand extends Command
{
    public function __construct(
        private PasswordResetService $passwordResetService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $deletedCount = $this->passwordResetService->cleanupExpiredTokens();
            
            if ($deletedCount > 0) {
                $io->success(sprintf('Successfully cleaned up %d expired password reset tokens.', $deletedCount));
            } else {
                $io->info('No expired password reset tokens found to clean up.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error during cleanup: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}