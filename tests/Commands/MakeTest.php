<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\KeyGeneratorService;

#[CoversNothing]
class MakeTest extends TestCase
{
    private string $serviceDirectory = __DIR__ . '/TestFiles/Service';
    private string $interfaceDirectory = __DIR__ . '/TestFiles/Interface/Service';

    private array $keepInterfaces = [
        'InterfaceA.php',
        'InterfaceB.php',
        'InterfaceC.php',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->cleanupFiles();
        $this->templatesToFiles();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->cleanupFiles();
    }

    public function testMake(): void
    {
        $originalServiceFile = $this->serviceDirectory . '/TargetClass.php';
        $cachedServiceFile = $this->serviceDirectory . '/TargetClassCached.php';
        $interfaceFile = $this->interfaceDirectory . '/TargetClassInterface.php';

        $makePath = realpath(__DIR__ . '/../../src/Commands/make.php');
        $this->assertNotFalse($makePath);

        $classDotNotation = str_replace('\\', '.', 'Tbessenreither\MultiLevelCache\Tests\Commands\TestFiles\Service\TargetClass');

        $command = sprintf('php %s %s 2>&1', $makePath, '--service=' . $classDotNotation);
        exec($command, $output, $resultCode);
        $this->assertSame(0, $resultCode, implode("\n", $output));

        $this->checkFileSyntax($originalServiceFile);
        $this->checkFileSyntax($cachedServiceFile);
        $this->checkFileSyntax($interfaceFile);

        $outputString = implode(PHP_EOL, $output);

        $this->assertStringContainsString('Cached service generated successfully.', $outputString);
        $this->assertStringContainsString($cachedServiceFile, $outputString);
        $this->assertStringContainsString($interfaceFile, $outputString);

        $cachedFileContent = file_get_contents($cachedServiceFile);
        $this->assertNotFalse($cachedFileContent);
        $this->assertStringContainsString('public function exampleMethod(): void', $cachedFileContent);
        $this->assertStringContainsString('public function exampleMethodWithArguments(array $thing, bool $bool, int $int, string $string): void', $cachedFileContent);

        $interfaceFileContent = file_get_contents($interfaceFile);
        $this->assertNotFalse($interfaceFileContent);
        $this->assertStringContainsString('public function exampleMethod(): void', $interfaceFileContent);
        $this->assertStringContainsString('public function exampleMethodWithArguments(array $thing, bool $bool, int $int, string $string): void', $interfaceFileContent);

        $this->assertStringContainsString("CACHE_KEY_PREFIX = '" . KeyGeneratorService::CACHED_SERVICE_GENERATOR_KEY_PREFIX . ":", $cachedFileContent);
    }

    public function testMakeWithInterfaces(): void
    {
        $makePath = realpath(__DIR__ . '/../../src/Commands/make.php');
        $this->assertNotFalse($makePath);

        $classDotNotation = str_replace('\\', '.', 'Tbessenreither\MultiLevelCache\Tests\Commands\TestFiles\Service\ServiceWithInterfaces');
        $cachedServiceFile = $this->serviceDirectory . '/ServiceWithInterfacesCached.php';
        $interfaceFile = $this->interfaceDirectory . '/ServiceWithInterfacesInterface.php';

        $command = sprintf('php %s %s 2>&1', $makePath, '--service=' . $classDotNotation);
        exec($command, $output, $resultCode);
        $this->assertSame(0, $resultCode, implode("\n", $output));

        $this->checkFileSyntax($cachedServiceFile);
        $this->checkFileSyntax($interfaceFile);

        $outputString = implode(PHP_EOL, $output);

        $this->assertStringContainsString('Cached service generated successfully.', $outputString);
        $this->assertStringContainsString($cachedServiceFile, $outputString);
        $this->assertStringContainsString($interfaceFile, $outputString);

        $cachedFileContent = file_get_contents($cachedServiceFile);
        $this->assertNotFalse($cachedFileContent);

        $lines = explode(PHP_EOL, $cachedFileContent);
        $lineWithInterfaces = null;
        foreach ($lines as $line) {
            if (str_contains($line, 'class ServiceWithInterfacesCached implements')) {
                $lineWithInterfaces = $line;

                break;
            }
        }
        $this->assertNotNull($lineWithInterfaces, 'Line with interfaces not found.');
        $this->assertStringContainsString('ServiceWithInterfacesCached', $lineWithInterfaces);
        $this->assertStringContainsString('InterfaceA', $lineWithInterfaces);
        $this->assertStringContainsString('InterfaceB', $lineWithInterfaces);
        $this->assertStringContainsString('InterfaceC', $lineWithInterfaces);

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

    private function cleanupFiles(): void
    {
        $files = scandir($this->serviceDirectory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (!str_ends_with($file, 'Template.txt')) {
                unlink($this->serviceDirectory . '/' . $file);
            }
        }

        $files = scandir($this->interfaceDirectory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (!in_array($file, $this->keepInterfaces, true)) {
                unlink($this->interfaceDirectory . '/' . $file);
            }
        };
    }

    private function templatesToFiles(): void
    {
        $files = scandir($this->serviceDirectory);
        if ($files === false) {
            $this->fail("Failed to read service directory.");
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $templateFilePath = $this->serviceDirectory . '/' . $file;
            if (!str_ends_with($file, 'Template.txt')) {
                unlink($templateFilePath);

                continue;
            }
            $realName = str_replace('Template.txt', '.php', $file);
            $realPath = $this->serviceDirectory . '/' . $realName;
            if (file_exists($realPath)) {
                unlink($realPath);
            }
            copy($templateFilePath, $realPath);
        }
    }

}
