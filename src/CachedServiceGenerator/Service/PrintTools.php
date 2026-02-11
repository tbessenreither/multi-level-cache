<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

class PrintTools
{
    public static function table(array $headers, array $rows): void
    {
        $rowsConverted = [];
        $table = [];
        //find longest string lengths for each column
        $maxLengths = array_map('strlen', $headers);
        foreach ($rows as $row) {
            $rowConverted = [];
            foreach($headers as $index => $header) {
                $value = array_shift($row);
                if(is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif(!is_string($value)) {
                    ob_start();
                    print_r($value, true);
                    $value = ob_get_clean();
                }
                $value = str_replace(PHP_EOL, ' ', $value);

                $rowConverted[] = $value;
                $length = strlen($value);
                if ($length > $maxLengths[$index]) {
                    $maxLengths[$index] = $length;
                }
            }
            $rowsConverted[] = $rowConverted;
        }

        //print header
        $headerCols = [];
        foreach ($headers as $index => $header) {
            $headerCols[] = str_pad($header, $maxLengths[$index] + 2);
        }
        $headerRow =  '| ' . implode(' | ', $headerCols) . ' |';
        $table[] = $headerRow;
        $table[] = str_repeat('-', strlen($headerRow));
        //print rows
        foreach ($rowsConverted as $row) {
            $cols = [];
            foreach ($row as $index => $value) {
                $cols[] = str_pad($value, $maxLengths[$index] + 2);
            }
            $table[] = '| ' . implode(' | ', $cols) . ' |';
        }
        echo implode(PHP_EOL, $table).PHP_EOL;
    }

    public static function line(string $line): void
    {
        echo $line . PHP_EOL;
    }

    public static function separator(int $length, string $char = '-'): void
    {
        echo str_repeat($char, $length) . PHP_EOL;
    }

    public static function headline(string $line): void
    {
        $xOffset = 10;
        self::separator(strlen($line) + $xOffset * 2, '=');
        echo str_repeat(' ', $xOffset). "\033[32m" . $line . "\033[0m" . PHP_EOL;
        self::separator(strlen($line) + $xOffset * 2, '=');
        echo PHP_EOL;
    }

    public static function subHeadline(string $line): void
    {
        $xOffset = 5;
        self::separator(strlen($line) + $xOffset * 2, '-');
        echo str_repeat(' ', $xOffset). "\033[34m" . $line . "\033[0m" . PHP_EOL;
        self::separator(strlen($line) + $xOffset * 2, '-');
        echo PHP_EOL;
    }

    public static function success(string $line): void
    {
        echo PHP_EOL."\033[32m" . $line . "\033[0m" . PHP_EOL;
    }

    public static function error(string $line): void
    {
        echo PHP_EOL."\033[31m" . $line . "\033[0m" . PHP_EOL;
    }

    public static function info(string $line): void
    {
        echo PHP_EOL."\033[34m" . $line . "\033[0m" . PHP_EOL;
    }
}
