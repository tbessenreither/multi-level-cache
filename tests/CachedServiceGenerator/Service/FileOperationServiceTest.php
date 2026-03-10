<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\FileOperationService;
use Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\FileOperationServiceClasses\TargetClass;

#[CoversClass(FileOperationService::class)]
class FileOperationServiceTest extends TestCase
{
    private string $newCachedFilePath = __DIR__ . '/FileOperationServiceClasses/TargetClassCached.php';
    private string $newCopiedFilePath = __DIR__ . '/FileOperationServiceClasses/TargetClassCopy.php';
    private string $originalClass = TargetClass::class;
    private string $newClass = TargetClass::class . 'Cached';

    public function setUp(): void
    {
        parent::setUp();

        if (file_exists($this->newCachedFilePath)) {
            unlink($this->newCachedFilePath);
        }
        if (file_exists($this->newCopiedFilePath)) {
            unlink($this->newCopiedFilePath);
        }

        $fileContent = file_get_contents(__DIR__ . '/FileOperationServiceClasses/TargetClass.php');
        $fileContent = str_replace('TargetClass', 'TargetClassCopy', $fileContent);
        file_put_contents($this->newCopiedFilePath, $fileContent);

        ob_start();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->newCachedFilePath)) {
            unlink($this->newCachedFilePath);
        }
        if (file_exists($this->newCopiedFilePath)) {
            unlink($this->newCopiedFilePath);
        }
        ob_get_clean();
    }

    public function testWriteFile(): void
    {
        $result = FileOperationService::writeFile($this->originalClass, $this->newClass, 'the new content');
        $this->assertIsString($result);
        $this->assertEquals($this->newCachedFilePath, realpath($result));
        $this->assertFileExists($this->newCachedFilePath);
        $this->checkFileSyntax($this->newCachedFilePath);
    }

    public function testAddInterfaceToClass(): void
    {
        FileOperationService::addInterfaceToClass(
            class: 'Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\FileOperationServiceClasses\TargetClassCopy',
            interface: 'Some\Namespace\TestInterface',
        );

        $code = file_get_contents($this->newCopiedFilePath);

        $this->assertStringContainsString('use Some\Namespace\TestInterface;', $code);
        $this->assertStringContainsString('implements TestInterface', $code);

        $this->checkFileSyntax($this->newCopiedFilePath);
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
