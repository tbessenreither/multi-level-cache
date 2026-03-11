<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto;

class MethodCallObject
{
    public function __construct(
        private string $class,
        private string $method,
        private array $arguments,
        private ?string $additionalCacheKey = null,
    ) {
    }

    public function getClass(): string
    {
        return $this->class;
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
