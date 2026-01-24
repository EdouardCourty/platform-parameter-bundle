<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;
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
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlatformParameterProviderInterface $provider,
        private readonly string $entityClass,
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

        $repository = $this->entityManager->getRepository($this->entityClass);
        $parameter = $repository->findOneBy(['key' => $key]);

        if (null === $parameter) {
            $io->error(\sprintf('Parameter "%s" not found.', $key));

            return Command::FAILURE;
        }

        \assert($parameter instanceof AbstractPlatformParameter);

        // Display parameter details
        $io->section('Parameter to delete:');
        $io->definitionList(
            ['Key' => $parameter->getKey()],
            ['Value' => $parameter->getValue()],
            ['Type' => $parameter->getType()->value],
            ['Label' => $parameter->getLabel()],
        );

        // Confirm deletion
        if (!$input->getOption('force') && $input->isInteractive()) {
            if (!$io->confirm('Are you sure you want to delete this parameter?', false)) {
                $io->info('Deletion cancelled.');

                return Command::SUCCESS;
            }
        }

        $this->entityManager->remove($parameter);
        $this->entityManager->flush();

        $this->provider->clearCache($key);

        $io->success(\sprintf('Parameter "%s" deleted successfully.', $key));

        return Command::SUCCESS;
    }
}
