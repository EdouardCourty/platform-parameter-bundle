<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'platform-parameter:get',
    description: 'Display a specific platform parameter with all its metadata',
)]
final class GetCommand extends Command
{
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $entityClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('key', InputArgument::REQUIRED, 'The parameter key');
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

        $io->title(\sprintf('Parameter: %s', $key));

        $io->definitionList(
            ['ID' => (string) $parameter->getId()], // @phpstan-ignore-line
            ['Key' => $parameter->getKey()],
            ['Value' => $parameter->getValue()],
            ['Type' => $parameter->getType()->value],
            ['Label' => $parameter->getLabel()],
            ['Description' => $parameter->getDescription() ?? '(none)'],
            ['Created At' => $parameter->getCreatedAt()->format('Y-m-d H:i:s')],
            ['Updated At' => $parameter->getUpdatedAt()->format('Y-m-d H:i:s')],
        );

        return Command::SUCCESS;
    }
}
