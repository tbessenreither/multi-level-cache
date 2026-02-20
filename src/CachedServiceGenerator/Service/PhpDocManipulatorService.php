<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

class PhpDocManipulatorService
{
    public static function add(string|false $docComment, string|array $linesToAdd, string $position = '@'): string
    {
        if ($docComment === false) {
            // Create a properly indented doc block
            $docComment = "/**\n*/";
        }

        if (!is_array($linesToAdd)) {
            $linesToAdd = [$linesToAdd];
        }

        $docComment = trim($docComment, '/* ');
        $docLines = explode("\n", $docComment);
        // remove trim spaces and leading * from lines
        $docLines = array_map(fn ($line) => trim(ltrim($line, '* ')), $docLines);
        // remove leading and trailing empty lines
        while (!empty($docLines) && trim($docLines[0]) === '') {
            array_shift($docLines);
        }
        while (!empty($docLines) && trim(end($docLines)) === '') {
            array_pop($docLines);
        }

        if ($position === 'description') {
            // find the first line with an @ and insert above if no line found insert at end of description
            $inserted = false;
            for ($i = 0; $i < count($docLines); $i++) {
                if (str_starts_with($docLines[$i], '@')) {
                    array_splice($docLines, $i, 0, $linesToAdd);
                    $inserted = true;
                    break;
                }
            }
            if (!$inserted) {
                $docLines = array_merge($docLines, $linesToAdd);
            }
        } elseif ($position === '@') {
            // find the last line with an @ and insert below, if no line found insert at end
            $inserted = false;
            for ($i = count($docLines) - 1; $i >= 0; $i--) {
                if (str_starts_with($docLines[$i], '@')) {
                    array_splice($docLines, $i + 1, 0, $linesToAdd);
                    $inserted = true;
                    break;
                }
            }
            if (!$inserted) {
                $docLines = array_merge($docLines, $linesToAdd);
            }
        }

        // rebuild doc comment with /** at the start and */ at the end. Each line prefixed with *
        $finalDoc = "/**\n";
        foreach ($docLines as $line) {
            $finalDoc .= '* ' . $line . "\n";
        }
        $finalDoc .= "*/";

        return self::indent($finalDoc);
    }

    public static function indent(string|false $docComment, int $level = 1): string
    {
        if ($docComment === false) {
            return '';
        }
        $lines = explode("\n", $docComment);
        $indentedLines = array_map(function ($line) use ($level) {
            $trimmedLine = trim($line);
            $indent = str_repeat('    ', $level);
            if (str_starts_with($trimmedLine, '*')) {
                $indent .= ' ';
            }
            return $indent . $trimmedLine;
        }, $lines);
        return implode("\n", $indentedLines);
    }
}
