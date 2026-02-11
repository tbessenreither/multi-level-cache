<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Exception;

use RuntimeException;
use Throwable;

class MlcUpdateCachedServiceException extends RuntimeException
{
    public function __construct(
        private string $type,
        private string $status = 'skipped',
        string $message = '',
        ?Throwable $previous = null,
    ) {
        if(!in_array($type, ['info', 'warning', 'error'], true)) {
            $type = 'error';
        }
        parent::__construct($message, 0, $previous);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
