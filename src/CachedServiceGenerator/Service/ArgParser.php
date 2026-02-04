<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

class ArgParser
{
    /**
     * Parses command line arguments into an associative array.
     * Supports arguments in the form of --key=value
     */
    public static function parse(): array
    {
        $result = [];
        global $argv;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $argTrimmed = trim(substr($arg, 2));

                if(empty($argTrimmed)) {
                    continue;
                }
                if(str_contains($argTrimmed, '=')) {
                    $parts = explode('=', $argTrimmed, 2);
                } else {
                    $parts = [$argTrimmed];
                }

                $key = mb_strtolower(array_shift($parts));

                if(count($parts) === 0) {
                    $result[$key] = true;
                } else {
                    $value = implode('=', $parts);
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }
}
