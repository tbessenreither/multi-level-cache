<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Interface;

interface DataCollectorIssueEnumInterface
{
    /**
     * Returns the name of the issue.
     */
    public function getName(): string;

    /**
     * Returns the descriptive value of the issue.
     */
    public function getDescription(): string;

    /**
     * Returns the type of the issue.
     */
    public function getType(): string;

    /**
     * Returns the badge class for the issue.
     * used in symfony data collector UI
     */
    public function getBadgeClass(): string;

    /**
     * Returns the status class for the issue.
     * used in symfony data collector UI
     */
    public function getStatusClass(): string;

}
