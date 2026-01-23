<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Command;

use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'platform-parameter:cache:clear',
    description: 'Clear platform parameters cache',
)]
final class CacheClearCommand extends Command
{
    public function __construct(
        private readonly PlatformParameterProviderInterface $provider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('key', InputArgument::OPTIONAL, 'Specific parameter key to clear (leave empty to clear all)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $key = $input->getArgument('key');
        \assert(null === $key || \is_string($key));

        $this->provider->clearCache($key);

        if (null !== $key) {
            $io->success(\sprintf('Cache cleared for parameter "%s".', $key));
        } else {
            $io->success('All platform parameters cache cleared.');
        }

        return Command::SUCCESS;
    }
}
