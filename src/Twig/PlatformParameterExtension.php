<?php

declare(strict_types=1);

namespace Ecourty\PlatformParameterBundle\Twig;

use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PlatformParameterExtension extends AbstractExtension
{
    public function __construct(
        private readonly PlatformParameterProviderInterface $provider,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('platform_parameter_string', $this->getString(...)),
            new TwigFunction('platform_parameter_int', $this->getInt(...)),
            new TwigFunction('platform_parameter_bool', $this->getBool(...)),
            new TwigFunction('platform_parameter_float', $this->getFloat(...)),
            new TwigFunction('platform_parameter_json', $this->getJson(...)),
            new TwigFunction('platform_parameter_list', $this->getList(...)),
            new TwigFunction('platform_parameter_datetime', $this->getDateTime(...)),
        ];
    }

    public function getString(string $key, ?string $default = null): ?string
    {
        try {
            return $this->provider->getString($key, $default);
        } catch (ParameterNotFoundException) {
            return null;
        }
    }

    public function getInt(string $key, ?int $default = null): ?int
    {
        try {
            return $this->provider->getInt($key, $default);
        } catch (ParameterNotFoundException) {
            return null;
        }
    }

    public function getBool(string $key, ?bool $default = null): ?bool
    {
        try {
            return $this->provider->getBool($key, $default);
        } catch (ParameterNotFoundException) {
            return null;
        }
    }

    public function getFloat(string $key, ?float $default = null): ?float
    {
        try {
            return $this->provider->getFloat($key, $default);
        } catch (ParameterNotFoundException) {
            return null;
        }
    }

    /**
     * @param array<mixed>|null $default
     *
     * @return array<mixed>|null
     */
    public function getJson(string $key, ?array $default = null): ?array
    {
        try {
            return $this->provider->getJson($key, $default);
        } catch (ParameterNotFoundException) {
            return null;
        }
    }

    /**
     * @param string[]|null $default
     *
     * @return string[]|null
     */
    public function getList(string $key, ?array $default = null, string $separator = "\n"): ?array
    {
        try {
            return $this->provider->getList($key, $default, $separator);
        } catch (ParameterNotFoundException) {
            return null;
        }
    }

    public function getDateTime(string $key, ?\DateTimeImmutable $default = null, ?string $format = null): ?\DateTimeImmutable
    {
        try {
            return $this->provider->getDateTime($key, $default, $format);
        } catch (ParameterNotFoundException) {
            return null;
        }
    }
}
