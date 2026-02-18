<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto;

class MethodCallObject
{
    public function __construct(
        private string $class,
        private string $method,
        private array $arguments,
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

}
