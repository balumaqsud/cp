<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\DemoSeedService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo-seed', description: 'Load idempotent demo users, attributes, positions, and CVs.')]
final class DemoSeedCommand extends Command
{
    public function __construct(
        private readonly DemoSeedService $demoSeed,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $accounts = $this->demoSeed->seed();

        $io->success('Demo data is ready. Password for every account: '.DemoSeedService::PASSWORD);
        $io->listing($accounts);

        return Command::SUCCESS;
    }
}
