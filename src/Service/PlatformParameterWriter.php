<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterWriterInterface;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Ecourty\PlatformParameterBundle\Exception\ParameterTypeMismatchException;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;

final class PlatformParameterWriter implements PlatformParameterWriterInterface
{
    /**
     * @param class-string<AbstractPlatformParameter> $entityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $entityClass,
    ) {
    }

    public function setString(string $key, string $value): void
    {
        $parameter = $this->fetchParameter($key);
        $this->validateType($key, ParameterType::STRING, $parameter->getType());

        $parameter->setValue(\trim($value));
        $this->entityManager->flush();
    }

    public function setInt(string $key, int $value): void
    {
        $parameter = $this->fetchParameter($key);
        $this->validateType($key, ParameterType::INTEGER, $parameter->getType());

        $parameter->setValue((string) $value);
        $this->entityManager->flush();
    }

    public function setBool(string $key, bool $value): void
    {
        $parameter = $this->fetchParameter($key);
        $this->validateType($key, ParameterType::BOOLEAN, $parameter->getType());

        $parameter->setValue($value ? '1' : '0');
        $this->entityManager->flush();
    }

    /**
     * @param array<mixed> $value
     */
    public function setJson(string $key, array $value): void
    {
        $parameter = $this->fetchParameter($key);
        $this->validateType($key, ParameterType::JSON, $parameter->getType());

        try {
            $encoded = \json_encode($value, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(\sprintf('Cannot encode value to JSON for parameter "%s": %s', $key, $e->getMessage()), 0, $e);
        }

        $parameter->setValue($encoded);
        $this->entityManager->flush();
    }

    /**
     * @param string[] $value
     */
    public function setList(string $key, array $value, string $separator = "\n"): void
    {
        $parameter = $this->fetchParameter($key);
        $this->validateType($key, ParameterType::LIST, $parameter->getType());

        \assert('' !== $separator, 'Separator cannot be empty');

        $parameter->setValue(\implode($separator, $value));
        $this->entityManager->flush();
    }

    public function setFloat(string $key, float $value): void
    {
        $parameter = $this->fetchParameter($key);
        $this->validateType($key, ParameterType::FLOAT, $parameter->getType());

        $parameter->setValue((string) $value);
        $this->entityManager->flush();
    }

    public function setDateTime(string $key, \DateTimeImmutable $value, ?string $format = null): void
    {
        $parameter = $this->fetchParameter($key);
        $this->validateType($key, ParameterType::DATETIME, $parameter->getType());

        $formatted = $value->format($format ?? \DateTimeInterface::ATOM);
        $parameter->setValue($formatted);
        $this->entityManager->flush();
    }

    public function delete(string $key): void
    {
        $parameter = $this->fetchParameter($key);

        $this->entityManager->remove($parameter);
        $this->entityManager->flush();
    }

    private function fetchParameter(string $key): AbstractPlatformParameter
    {
        $repository = $this->entityManager->getRepository($this->entityClass);
        /** @var AbstractPlatformParameter|null $parameter */
        $parameter = $repository->findOneBy(['key' => $key]);

        if (null === $parameter) {
            throw ParameterNotFoundException::forKey($key);
        }

        return $parameter;
    }

    private function validateType(string $key, ParameterType $expectedType, ParameterType $actualType): void
    {
        if ($expectedType !== $actualType) {
            throw ParameterTypeMismatchException::create($key, $expectedType, $actualType);
        }
    }
}
