<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class MakeTest extends TestCase
{
    private string $originalServiceTemplate = __DIR__.'/TestFiles/Service/TargetClassTemplate.txt';
    private string $originalServiceClass = 'Tbessenreither\MultiLevelCache\Tests\Commands\TestFiles\Service\TargetClass';
    private string $originalServiceFile = __DIR__.'/TestFiles/Service/TargetClass.php';
    private string $cachedServiceFile = __DIR__.'/TestFiles/Service/TargetClassCached.php';
    private string $interfaceFile = __DIR__.'/TestFiles/Interface/Service/TargetClassInterface.php';

    public function setUp(): void
    {
        parent::setUp();

        if (file_exists($this->originalServiceFile)) {
            unlink($this->originalServiceFile);
        }
        if (file_exists($this->cachedServiceFile)) {
            unlink($this->cachedServiceFile);
        }
        if (file_exists($this->interfaceFile)) {
            unlink($this->interfaceFile);
            rmdir(__DIR__.'/TestFiles/Interface/Service');
            rmdir(__DIR__.'/TestFiles/Interface');
        }

        copy($this->originalServiceTemplate, $this->originalServiceFile);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->originalServiceFile)) {
            unlink($this->originalServiceFile);
        }
        if (file_exists($this->cachedServiceFile)) {
            unlink($this->cachedServiceFile);
        }
        if (file_exists($this->interfaceFile)) {
            unlink($this->interfaceFile);
        }
    }

    public function testMake(): void
    {
        $makePath = realpath(__DIR__.'/../../src/Commands/make.php');
        $this->assertNotFalse($makePath);

        $classDotNotation = str_replace('\\', '.', $this->originalServiceClass);

        $command = sprintf('php %s %s 2>&1', $makePath, '--service='.$classDotNotation);
        exec($command, $output, $resultCode);
        $this->assertSame(0, $resultCode, implode("\n", $output));

        $this->checkFileSyntax($this->originalServiceFile);
        $this->checkFileSyntax($this->cachedServiceFile);
        $this->checkFileSyntax($this->interfaceFile);

        $outputString = implode(PHP_EOL, $output);

        $this->assertStringContainsString('Cached service generated successfully.', $outputString);
        $this->assertStringContainsString($this->cachedServiceFile, $outputString);
        $this->assertStringContainsString($this->interfaceFile, $outputString);

        $cachedFileContent = file_get_contents($this->cachedServiceFile);
        $this->assertNotFalse($cachedFileContent);
        $this->assertStringContainsString('public function exampleMethod(): void', $cachedFileContent);
        $this->assertStringContainsString('public function exampleMethodWithArguments(array $thing, bool $bool, int $int, string $string): void', $cachedFileContent);

        $interfaceFileContent = file_get_contents($this->interfaceFile);
        $this->assertNotFalse($interfaceFileContent);
        $this->assertStringContainsString('public function exampleMethod(): void', $interfaceFileContent);
        $this->assertStringContainsString('public function exampleMethodWithArguments(array $thing, bool $bool, int $int, string $string): void', $interfaceFileContent);

    }

    private function checkFileSyntax(string $file): void
    {
        if (!file_exists($file)) {
            $this->fail("File '$file' does not exist.");
        }
        // run PHP lint
        exec(sprintf('php -l %s 2>&1', escapeshellarg($file)), $output, $resultCode);

        $this->assertSame(0, $resultCode, implode("\n", $output));
    }

}
