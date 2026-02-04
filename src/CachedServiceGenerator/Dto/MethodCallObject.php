<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto;

class MethodCallObject
{
    public function __construct(
        private string $method,
        private array $arguments,
    ) {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

}
