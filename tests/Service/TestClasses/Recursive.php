<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service\TestClasses;

class Recursive
{
    public function __construct(
        public int $id,
        public mixed $value,
        public ?self $nested = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getNested(): ?self
    {
        return $this->nested;
    }
}
