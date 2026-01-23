<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ecourty\PlatformParameterBundle\Entity\AbstractPlatformParameter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'platform-parameter:list',
    description: 'List all platform parameters',
)]
final class ListCommand extends Command
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $repository = $this->entityManager->getRepository($this->entityClass);
        $parameters = $repository->findAll();

        if (empty($parameters)) {
            $io->warning('No parameters found.');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($parameters as $parameter) {
            \assert($parameter instanceof AbstractPlatformParameter);

            $value = $parameter->getValue();
            if (\mb_strlen($value) > 50) {
                $value = \mb_substr($value, 0, 47).'...';
            }

            $rows[] = [
                $parameter->getKey(),
                $value,
                $parameter->getType()->value,
                $parameter->getLabel(),
            ];
        }

        $io->table(['Key', 'Value', 'Type', 'Label'], $rows);
        $io->success(\sprintf('Found %d parameter(s).', \count($parameters)));

        return Command::SUCCESS;
    }
}
