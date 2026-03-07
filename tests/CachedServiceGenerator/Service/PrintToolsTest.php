<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\PrintTools;

#[CoversClass(PrintTools::class)]
class PrintToolsTest extends TestCase
{
    public function testTable(): void
    {
        $tableData = [
            ['Alice', 30, true],
            ['Bob', 25, false],
            ['Charlie', 35, true],
            ['David', 28, ['sub' => 'value']],
        ];
        ob_start();
        PrintTools::table(['Name', 'Age', 'Active'], $tableData);
        $output = ob_get_clean();

        foreach ($tableData as $row) {
            foreach ($row as $col) {
                if (is_bool($col)) {
                    $col = $col ? 'true' : 'false';
                } elseif (is_int($col) || is_float($col)) {
                    $col = (string)$col;
                } elseif (!is_string($col)) {
                    $col = json_encode($col);
                }
                $this->assertStringContainsString((string)$col, $output);
            }
        }
        $this->assertStringEndsWith(PHP_EOL, $output);

    }

    public function testLine(): void
    {
        ob_start();
        PrintTools::line('Hello, World!');
        $output = ob_get_clean();
        $this->assertStringContainsString("Hello, World!", $output);
        $this->assertStringEndsWith(PHP_EOL, $output);
    }

    public function testSeparator(): void
    {
        ob_start();
        PrintTools::separator(20);
        $output = ob_get_clean();
        $this->assertEquals(str_repeat('-', 20) . "\n", $output);
        $this->assertStringEndsWith(PHP_EOL, $output);

        ob_start();
        PrintTools::separator(10, '*');
        $output = ob_get_clean();
        $this->assertEquals(str_repeat('*', 10) . "\n", $output);
        $this->assertStringEndsWith(PHP_EOL, $output);
    }

    public function testHeadline(): void
    {
        ob_start();
        PrintTools::headline('Test Headline');
        $output = ob_get_clean();
        $this->assertStringContainsString("Test Headline", $output);
        $this->assertStringEndsWith(PHP_EOL, $output);
    }

    public function testSubHeadline(): void
    {
        ob_start();
        PrintTools::subHeadline('Test Subheadline');
        $output = ob_get_clean();
        $this->assertStringContainsString("Test Subheadline", $output);
        $this->assertStringEndsWith(PHP_EOL, $output);
    }

    public function testSuccess(): void
    {
        ob_start();
        PrintTools::success('Operation successful');
        $output = ob_get_clean();
        $this->assertStringContainsString('Operation successful', $output);
        $this->assertStringEndsWith(PHP_EOL, $output);
    }

    public function testError(): void
    {
        ob_start();
        PrintTools::error('An error occurred');
        $output = ob_get_clean();
        $this->assertStringContainsString('An error occurred', $output);
        $this->assertStringEndsWith(PHP_EOL, $output);
    }

    public function testInfo(): void
    {
        ob_start();
        PrintTools::info('This is an info message');
        $output = ob_get_clean();
        $this->assertStringContainsString('This is an info message', $output);
        $this->assertStringEndsWith(PHP_EOL, $output);
    }

}
