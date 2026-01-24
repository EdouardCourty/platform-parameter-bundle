<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Command;

use Ecourty\PlatformParameterBundle\Contract\PlatformParameterWriterInterface;
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'platform-parameter:delete',
    description: 'Delete a platform parameter',
)]
final class DeleteCommand extends Command
{
    public function __construct(
        private readonly PlatformParameterWriterInterface $writer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'The parameter key')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force deletion without confirmation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $key */
        $key = $input->getArgument('key');

        try {
            // Confirm deletion
            if (!$input->getOption('force') && $input->isInteractive()) {
                if (!$io->confirm(\sprintf('Are you sure you want to delete parameter "%s"?', $key), false)) {
                    $io->info('Deletion cancelled.');

                    return Command::SUCCESS;
                }
            }

            $this->writer->delete($key);

            $io->success(\sprintf('Parameter "%s" deleted successfully.', $key));

            return Command::SUCCESS;
        } catch (ParameterNotFoundException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
