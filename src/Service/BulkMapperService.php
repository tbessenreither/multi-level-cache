<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Service;

use InvalidArgumentException;

class BulkMapperService
{
    public static function mapArrayNumeric(array $responses): array
    {
        return array_values($responses);
    }

    public static function mapArrayAssoc(array $responses, string $identifierSelector): array
    {
        $mapped = [];
        foreach ($responses as $response) {
            if (!is_array($response) && !is_object($response)) {
                continue;
            }
            $id = self::getIdentifierFromObjectResult($response, $identifierSelector);
            $mapped[$id] = $response;
        }

        return $mapped;
    }

    public static function getIdentifierFromObjectResult(mixed $result, string $identifierSelector): int|string
    {
        $selectorParts = explode('.', $identifierSelector);
        if (count($selectorParts) === 1 && empty($selectorParts[0])) {
            throw new InvalidArgumentException('Identifier selector must not be empty.');
        }

        $currentSelection = $result;
        do {
            $nextPart = array_shift($selectorParts);
            if (empty($nextPart)) {
                throw new InvalidArgumentException('Identifier selector must not contain empty parts.');
            }

            if (is_array($currentSelection)) {
                $currentSelection = $currentSelection[$nextPart];
            } elseif (is_object($currentSelection)) {
                $currentSelection = $currentSelection->{$nextPart}();
            } elseif (is_string($currentSelection) || is_int($currentSelection)) {
                return $currentSelection;
            } else {
                throw new InvalidArgumentException('Identifier selector is not valid. Expected array or object but got ' . gettype($currentSelection));
            }
        } while (!empty($selectorParts));

        return $currentSelection;
    }
}
