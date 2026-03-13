<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Dto;

use Tbessenreither\MultiLevelCache\Interface\DataCollectorIssueEnumInterface;

class DataCollectorIssueDto
{
    /**
     * @var array<string, DataCollectorIssueOccurrenceDto> $occurrences
     */
    private array $occurrences = [];

    public function __construct(
        private string $name,
        private string $value,
        private string $type,
        private string $badgeClass,
        private string $statusClass,
    ) {
    }

    public static function fromEnum(DataCollectorIssueEnumInterface $enum): self
    {
        return new self(
            name: $enum->getName(),
            value: $enum->getDescription(),
            type: $enum->getType(),
            badgeClass: $enum->getBadgeClass(),
            statusClass: $enum->getStatusClass(),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBadgeClass(): string
    {
        return $this->badgeClass;
    }

    public function getStatusClass(): string
    {
        return $this->statusClass;
    }

    public function addOccurrence(DataCollectorIssueOccurrenceDto $occurrence): void
    {
        $this->occurrences[$occurrence->getUniqueHash()] = $occurrence;
    }

    /**
     * @return array<int, DataCollectorIssueOccurrenceDto>
     */
    public function getOccurrences(): array
    {
        return array_values($this->occurrences);
    }

}
