<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Something\Completely\Different\CorruptedNamespace;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\FileOperationService;
use Tbessenreither\MultiLevelCache\Tests\TestFiles\FileOperationServiceClasses\Other\TargetClass as TargetClassOther;
use Tbessenreither\MultiLevelCache\Tests\TestFiles\FileOperationServiceClasses\Resource\TargetClass as TargetClassResource;
use Tbessenreither\MultiLevelCache\Tests\TestFiles\FileOperationServiceClasses\Service\TargetClass as TargetClassService;

#[CoversClass(FileOperationService::class)]
class FileOperationServiceTest extends TestCase
{
    private string $newCachedFilePath;
    private string $newCopiedFilePath;
    private string $originalClass = TargetClassService::class;
    private string $newClass = TargetClassService::class . 'Cached';

    private string $projectRootPath;
    private string $testFilesRootPath;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->projectRootPath = realpath(__DIR__ . '/../../..');
        $this->testFilesRootPath = $this->projectRootPath . '/tests/TestFiles/FileOperationServiceClasses';

        $this->newCachedFilePath = $this->testFilesRootPath . '/Service/TargetClassCached.php';
        $this->newCopiedFilePath = $this->testFilesRootPath . '/Service/TargetClassCopy.php';
    }

    public function setUp(): void
    {
        parent::setUp();

        if (file_exists($this->newCachedFilePath)) {
            unlink($this->newCachedFilePath);
        }
        if (file_exists($this->newCopiedFilePath)) {
            unlink($this->newCopiedFilePath);
        }

        $fileContent = file_get_contents($this->testFilesRootPath . '/Service/TargetClass.php');
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
            class: 'Tbessenreither\MultiLevelCache\Tests\TestFiles\FileOperationServiceClasses\Service\TargetClassCopy',
            interface: 'Some\Namespace\TestInterface',
        );

        $code = file_get_contents($this->newCopiedFilePath);

        $this->assertStringContainsString('use Some\Namespace\TestInterface;', $code);
        $this->assertStringContainsString('implements TestInterface', $code);

        $this->checkFileSyntax($this->newCopiedFilePath);
    }

    public function testFindRootForClassInResource(): void
    {
        $result = FileOperationService::findRootForClass(TargetClassResource::class);
        $expected = [
            'namespace' => 'Tbessenreither\\MultiLevelCache\\Tests\\TestFiles\\FileOperationServiceClasses\\Resource\\',
            'path' => $this->testFilesRootPath . '/Resource/',
        ];
        $this->assertEquals($result, $expected);
    }

    public function testFindRootForClassInOther(): void
    {
        $result = FileOperationService::findRootForClass(TargetClassOther::class);
        $expected = [
            'namespace' => 'Tbessenreither\\MultiLevelCache\\Tests\\',
            'path' => $this->projectRootPath . '/tests/',
        ];
        $this->assertEquals($result, $expected);
    }

    public function testFindRootForClassWithCorruptedNamespace(): void
    {
        require_once($this->testFilesRootPath . '/CorruptedNamespace.php');
        $this->expectException(RuntimeException::class);
        $result = FileOperationService::findRootForClass(CorruptedNamespace::class);
        var_dump($result);
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
