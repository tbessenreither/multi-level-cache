<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\FileOperationServiceClasses;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\FileOperationService;

class FileOperationServiceWrapper extends FileOperationService
{
    private static array $writtenFiles = [];
    private static function writeFileToDisk(string $file, string $contents): void
    {
        self::$writtenFiles[$file] = $contents;
    }

    public static function getWrittenFiles(): array
    {
        return self::$writtenFiles;
    }
    public static function clearWrittenFiles(): void
    {
        self::$writtenFiles = [];
    }
}
