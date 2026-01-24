<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'platform-parameter:set',
    description: 'Create or update a platform parameter',
)]
final class SetCommand extends Command
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
            ->addArgument('value', InputArgument::REQUIRED, 'The parameter value')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Parameter type (for creation)')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Parameter label (for creation)')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Parameter description (for creation)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $key */
        $key = $input->getArgument('key');
        /** @var string $value */
        $value = $input->getArgument('value');

        $repository = $this->entityManager->getRepository($this->entityClass);
        $parameter = $repository->findOneBy(['key' => $key]);

        if (null !== $parameter) {
            \assert($parameter instanceof AbstractPlatformParameter);

            // Update existing parameter
            $parameter->setValue($value);
            $this->entityManager->flush();

            $this->provider->clearCache($key);

            $io->success(\sprintf('Parameter "%s" updated successfully.', $key));

            return Command::SUCCESS;
        }

        // Create new parameter
        $type = $this->getType($input, $io);
        if (null === $type) {
            return Command::FAILURE;
        }

        $label = $this->getLabel($input, $io);
        if (null === $label) {
            return Command::FAILURE;
        }

        $description = $this->getParameterDescription($input, $io);

        $parameter = new ($this->entityClass)();
        \assert($parameter instanceof AbstractPlatformParameter);

        $parameter->setKey($key);
        $parameter->setValue($value);
        $parameter->setType($type);
        $parameter->setLabel($label);
        $parameter->setDescription($description);

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();

        $this->provider->clearCache($key);

        $io->success(\sprintf('Parameter "%s" created successfully.', $key));

        return Command::SUCCESS;
    }

    private function getType(InputInterface $input, SymfonyStyle $io): ?ParameterType
    {
        /** @var string|null $typeOption */
        $typeOption = $input->getOption('type');

        if (null !== $typeOption) {
            $type = ParameterType::tryFrom($typeOption);
            if (null === $type) {
                $io->error(\sprintf('Invalid type "%s". Valid types: %s', $typeOption, \implode(', ', \array_column(ParameterType::cases(), 'value'))));

                return null;
            }

            return $type;
        }

        if ($input->isInteractive()) {
            $typeChoices = \array_map(static fn (ParameterType $t) => $t->value, ParameterType::cases());
            /** @var string $typeValue */
            $typeValue = $io->choice('Select parameter type', $typeChoices, 'string');

            return ParameterType::from($typeValue);
        }

        $io->error('--type option is required in non-interactive mode.');

        return null;
    }

    private function getLabel(InputInterface $input, SymfonyStyle $io): ?string
    {
        /** @var string|null $label */
        $label = $input->getOption('label');

        if (null !== $label) {
            return $label;
        }

        if ($input->isInteractive()) {
            /** @var string|null $result */
            $result = $io->ask('Enter parameter label');

            return $result;
        }

        $io->error('--label option is required in non-interactive mode.');

        return null;
    }

    private function getParameterDescription(InputInterface $input, SymfonyStyle $io): ?string
    {
        /** @var string|null $description */
        $description = $input->getOption('description');

        if (null !== $description) {
            return $description;
        }

        if ($input->isInteractive()) {
            /** @var string|null $result */
            $result = $io->ask('Enter parameter description (optional)', null);

            return $result;
        }

        return null;
    }
}
