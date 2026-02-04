<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

use ReflectionClass;
use RuntimeException;

class MlcFileOperationService
{
    public static function writeFile(string $originalClass, string $class, string $code): string
    {
        $targetFile = self::getFilePathFromClassString(
            originalClass: $originalClass,
            class: $class,
        );

        self::ensureDirectoryExists(dirname($targetFile));
        self::writeFileToDisk($targetFile, $code);
        return $targetFile;
    }

    /**
     * @return array{namespace: string, path: string}
     */
    public static function findRootForClass(string $class): array
    {
        $originalClassReflection = new ReflectionClass($class);
        $originalFilePath = $originalClassReflection->getFileName();

        // Get the namespace and path parts for the original class
        $originalNamespace = $originalClassReflection->getNamespaceName();
        $originalPath = dirname($originalFilePath);


        $namespaceParts = explode('\\', $originalNamespace);
        $pathParts = explode(DIRECTORY_SEPARATOR, $originalPath);


        $found = false;

        if(in_array('Service', $namespaceParts, true)) {
            $found = [
                'namespacePartKey' => array_search('Service', $namespaceParts, true),
                'pathPartKey' => array_search('Service', $pathParts, true),
            ];
        } elseif(in_array('Resource', $namespaceParts, true)) {
            $found = [
                'namespacePartKey' => array_search('Resource', $namespaceParts, true) + 2,
                'pathPartKey' => array_search('Resource', $pathParts, true) + 2,
            ];
        } else {
            foreach($namespaceParts as $namespacePartKey => $namespacePart) {
                foreach($pathParts as $pathPartKey => $pathPart) {
                    if($namespacePart === $pathPart) {
                        $found = [
                            'namespacePartKey' => $namespacePartKey,
                            'pathPartKey' => $pathPartKey,
                        ];
                        break;
                    }
                }
                if($found !== false) {
                    break;
                }
            }
        }

        if($found === false) {
            throw new RuntimeException('Could not map namespace to path for class: ' . $class.' and path '.$originalPath);
        }

        $rootDirectory = implode(DIRECTORY_SEPARATOR, array_slice($pathParts, 0, $found['pathPartKey'])).DIRECTORY_SEPARATOR;
        $psr4Root = implode('\\', array_slice($namespaceParts, 0, $found['namespacePartKey'])).'\\';

        return [
            'namespace' => $psr4Root,
            'path' => $rootDirectory
        ];
    }

    public static function addInterfaceToClass(string $class, string $interface): void
    {
        $interfaceNameParts = explode('\\', $interface);
        $interfaceShortName = end($interfaceNameParts);

        $useStatement = "use $interface;";

        $filePath = self::getFilePathFromClassString(
            originalClass: $class,
            class: $class,
        );

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new RuntimeException("Failed to read file: $filePath");
        }

        if(str_contains($contents, $useStatement)) {
            return;
        } else {
            echo "Use statement for interface '$interface' does not exists in file: $filePath" . PHP_EOL;
            echo "Attempt adding it." . PHP_EOL;
        }

        $fileLines = explode(PHP_EOL, $contents);

        $lineWithClassDeclaration = null;
        foreach ($fileLines as $lineNumber => $line) {
            if (str_contains($line, 'class ' . (new ReflectionClass($class))->getShortName())) {
                $lineWithClassDeclaration = $lineNumber;
                break;
            }
        }

        if ($lineWithClassDeclaration === null) {
            echo "Class declaration not found in file: $filePath. Please add the interface '$interface' to the class yourself." . PHP_EOL;
            return;
        }

        $declarationLine = &$fileLines[$lineWithClassDeclaration];
        if(str_contains($declarationLine, $interfaceShortName)) {
            echo "Class '$class' already implements interface '$interface' in file: $filePath" . PHP_EOL;
            return;
        }

        // Insert the use statement before the class declaration
        // search the position of the use statements
        $insertUseAtLine = self::getLineToInsertUseStatement($fileLines);
        if($insertUseAtLine === null) {
            throw new RuntimeException("Could not find position to insert use statement in file: $filePath");
        }
        array_splice($fileLines, $insertUseAtLine, 0, $useStatement);

        // Modify the class declaration to implement the interface
        if (str_contains($declarationLine, ' implements ')) {
            $declarationLine = str_replace(' implements ', ' implements ' . $interfaceShortName . ', ', $declarationLine);
        } else {
            $classPart = 'class ' . (new ReflectionClass($class))->getShortName();
            $declarationLine = str_replace($classPart, $classPart . ' implements ' . $interfaceShortName, $declarationLine);
        }

        $newContents = implode(PHP_EOL, $fileLines);

        file_put_contents($filePath, $newContents);
        echo "Added interface '$interface' to class '$class' in file: $filePath" . PHP_EOL;
    }

    private static function getLineToInsertUseStatement(array $fileLines): ?int
    {
        $insertUseAfterLine = null;

        // Try to group Interfaces together
        if($insertUseAfterLine === null) {
            foreach ($fileLines as $lineNumber => $line) {
                if (
                    str_starts_with(trim($line), 'use ')
                    && str_contains($line, '\\Interface\\')
                ) {
                    $insertUseAfterLine = $lineNumber;
                }
            }
        }
        // Just search for the last use line
        if($insertUseAfterLine === null) {
            foreach ($fileLines as $lineNumber => $line) {
                if (str_starts_with(trim($line), 'use ')) {
                    $insertUseAfterLine = $lineNumber;
                }
            }
        }
        // Give up and just put it after the namespace
        if($insertUseAfterLine === null) {
            foreach($fileLines as $lineNumber => $line) {
                if (str_starts_with(trim($line), 'namespace ')) {
                    $insertUseAfterLine = $lineNumber;
                    break;
                }
            }
        }
        return $insertUseAfterLine + 1;
    }

    private static function getFilePathFromClassString(string $originalClass, string $class): string
    {
        $rootInfo = self::findRootForClass($originalClass);
        $psr4Root = $rootInfo['namespace'];
        $rootDirectory = $rootInfo['path'];

        if(!str_starts_with($class, $psr4Root)) {
            throw new RuntimeException("Class '$class' does not start with expected PSR-4 root '$psr4Root'.");
        }

        $relativeClassPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($psr4Root))) . '.php';
        $absoluteClassPath = $rootDirectory . $relativeClassPath;

        return $absoluteClassPath;
    }

    /**
     * Ensure the directory exists, create it if not.
     */
    private static function ensureDirectoryExists(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException("Failed to create directory: $dir");
            }
        }
    }

    /**
     * Write contents to a file, throw on failure.
     */
    private static function writeFileToDisk(string $file, string $contents): void
    {
        if (file_put_contents($file, $contents) === false) {
            throw new RuntimeException("Failed to write file: $file");
        }
    }
}
