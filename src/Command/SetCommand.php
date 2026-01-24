<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterWriterInterface;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'platform-parameter:set',
    description: 'Update an existing platform parameter',
)]
final class SetCommand extends Command
{
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlatformParameterWriterInterface $writer,
        private readonly string $entityClass,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'The parameter key')
            ->addArgument('value', InputArgument::REQUIRED, 'The parameter value')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $key */
        $key = $input->getArgument('key');
        /** @var string $value */
        $value = $input->getArgument('value');

        try {
            // Get existing parameter to determine its type
            $repository = $this->entityManager->getRepository($this->entityClass);
            $parameter = $repository->findOneBy(['key' => $key]);

            if (null === $parameter) {
                $io->error(\sprintf('Parameter "%s" not found. Use Doctrine entities to create new parameters.', $key));

                return Command::FAILURE;
            }

            \assert($parameter instanceof AbstractPlatformParameter);

            // Use Writer method based on type
            match ($parameter->getType()) {
                ParameterType::STRING => $this->writer->setString($key, $value),
                ParameterType::INTEGER => $this->writer->setInt($key, (int) $value),
                ParameterType::BOOLEAN => $this->writer->setBool($key, \filter_var($value, \FILTER_VALIDATE_BOOLEAN)),
                ParameterType::JSON => $this->writer->setJson($key, (array) \json_decode($value, true, 512, \JSON_THROW_ON_ERROR)),
                ParameterType::LIST => $this->writer->setList($key, \explode("\n", $value)),
                ParameterType::FLOAT => $this->writer->setFloat($key, (float) $value),
                ParameterType::DATETIME => $this->writer->setDateTime($key, new \DateTimeImmutable($value)),
            };

            $io->success(\sprintf('Parameter "%s" updated successfully.', $key));

            return Command::SUCCESS;
        } catch (ParameterNotFoundException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error(\sprintf('Failed to update parameter: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
