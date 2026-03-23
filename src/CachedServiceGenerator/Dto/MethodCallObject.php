<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\KeyGeneratorService;

class MethodCallObject
{
    public function __construct(
        private object|string $class,
        private string $method,
        private array $arguments,
        private ?string $additionalCacheKey = null,
    ) {
    }

    public function getClass(): string
    {
        return is_object($this->class) ? get_class($this->class) : $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getCacheGeneratorCacheKey(): string
    {
        return $this->class . ':' . $this->method;
    }

    public function getAdditionalCacheKey(): ?string
    {
        return $this->additionalCacheKey;
    }

    public function getCachePrefix(): string
    {
        return KeyGeneratorService::namespaceToKeyString($this->getClass());
    }

    public function getCallable(): callable
    {
        return fn () => call_user_func_array([$this->class, $this->method], $this->arguments);
    }

    public function clone(?string $class = null, ?string $method = null, ?array $arguments = null, ?string $additionalCacheKey = null): self
    {
        return new self(
            class: $class ?? $this->class,
            method: $method ?? $this->method,
            arguments: $arguments ?? $this->arguments,
            additionalCacheKey: $additionalCacheKey ?? $this->additionalCacheKey,
        );
    }
}
