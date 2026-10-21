<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Security\ApiTokenHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:api-token', description: 'Generate a new API token for a user')]
final class GenerateApiTokenCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower((string) $input->getArgument('email'));

        $user = $this->users->findOneBy(['email' => $email]);
        if ($user === null) {
            $io->error(sprintf('No user found for "%s".', $email));

            return Command::FAILURE;
        }

        $token = bin2hex(random_bytes(32));
        $user->setApiToken(ApiTokenHandler::hash($token));
        $this->em->flush();

        $io->success(sprintf('New token for %s (shown only once):', $user->getFullName()));
        $io->writeln($token);

        return Command::SUCCESS;
    }
}
