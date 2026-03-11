<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service\Implementations;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;
use Symfony\Component\HttpKernel\KernelInterface;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Service\Implementations\FileCacheService;

#[CoversClass(FileCacheService::class)]


class FileCacheServiceTest extends TestCase
{
    private FileCacheService $fileCacheService;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/file_cache_service_test';
        $this->fileCacheService = new FileCacheService(
            kernel: $this->getKernelMockWithCacheDir($this->cacheDir),
            keyPrefix: 'test_prefix',
        );
        $this->fileCacheService->clear();
    }

    protected function tearDown(): void
    {
        $this->fileCacheService->clear();

        if (is_dir($this->cacheDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
            rmdir($this->cacheDir);
        }
    }

    public function testSetAndGet(): void
    {
        $key = 'test_key_set_get';
        $dto = $this->createMock(CacheObjectWrapperDto::class);
        $dto->method('isExpired')->willReturn(false);

        $this->fileCacheService->set($key, $dto);
        $retrievedDto = $this->fileCacheService->get($key);

        $this->assertNotNull($retrievedDto);
        $this->assertInstanceOf(CacheObjectWrapperDto::class, $retrievedDto);
    }

    public function testGetReturnsNullForNonExistentKey(): void
    {
        $this->assertNull($this->fileCacheService->get('non_existent_key'));
    }

    public function testDelete(): void
    {
        $key = 'test_key_delete';
        $dto = $this->createMock(CacheObjectWrapperDto::class);
        $dto->method('isExpired')->willReturn(false);

        $this->fileCacheService->set($key, $dto);
        $this->fileCacheService->delete($key);

        $this->assertNull($this->fileCacheService->get($key));
    }

    public function testGetCachedKeys(): void
    {
        $key1 = 'key1';
        $key2 = 'key2';
        $dto = $this->createMock(CacheObjectWrapperDto::class);
        $dto->method('isExpired')->willReturn(false);

        $this->fileCacheService->set($key1, $dto);
        $this->fileCacheService->set($key2, $dto);

        $cachedKeys = $this->fileCacheService->getCachedKeys();

        $this->assertContains($key1, $cachedKeys, 'Key1 should be in the cached keys');
        $this->assertContains($key2, $cachedKeys, 'Key2 should be in the cached keys');
    }

    public function testGetConfiguration(): void
    {
        $config = $this->fileCacheService->getConfiguration();

        $this->assertArrayHasKey('prefix', $config);
        $this->assertArrayHasKey('cacheDir', $config);
        $this->assertArrayHasKey('serialization', $config);
        $this->assertEquals('php_serialize', $config['serialization']);
    }

    public function testConstructorThrowsExceptionForInvalidCacheCleanupChance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cacheCleanupChanceOneOver must be a positive integer greater than 0.');

        new FileCacheService(
            kernel: $this->getKernelMockWithCacheDir($this->cacheDir),
            keyPrefix: 'test_prefix',
            cacheCleanupChanceOneOver: 0 // Invalid value
        );
    }

    public function testGetReturnsNullWhenSerializedDataIsFalse(): void
    {
        $key = 'test_key';
        $this->writeObjectToCacheFile($key, false);

        $this->assertNull($this->fileCacheService->get($key));
    }

    public function testGetReturnsNullWhenUnserializeFails(): void
    {
        $key = 'test_key';
        $this->writeObjectToCacheFile($key . '1', 'invalid_data');
        $this->writeObjectToCacheFile($key . '2', new stdClass());

        $this->assertNull($this->fileCacheService->get($key . '1'));
        $this->assertNull($this->fileCacheService->get($key . '2'));
    }

    public function testGetReturnsNullWhenObjectIsExpired(): void
    {
        $key = 'test_key_expired';
        $dto = $this->createMock(CacheObjectWrapperDto::class);
        $dto->method('isExpired')->willReturn(true);

        $fileCacheService = new FileCacheService(
            kernel: $this->getKernelMockWithCacheDir($this->cacheDir),
            keyPrefix: '',
            randomCleanupInSet: false,
        );

        $this->fileCacheService->set($key, $dto);
        $this->assertNotEmpty($fileCacheService->getCachedKeys());
        usleep(1000);
        $this->assertNull($this->fileCacheService->get($key));
        usleep(1000);
        $this->assertEmpty($fileCacheService->getCachedKeys());
    }

    public function testClearAllCacheFiles(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $key = 'key' . $i;
            $this->writeObjectToCacheFile($key, $this->createMock(CacheObjectWrapperDto::class));
        }
        $this->assertNotEmpty($this->fileCacheService->getCachedKeys());
        $this->fileCacheService->clear();
        $this->assertEmpty($this->fileCacheService->getCachedKeys());
    }

    public function testRemoveExpiredCacheFiles(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $key = 'valid_key' . $i;
            $dto = $this->createMock(CacheObjectWrapperDto::class);
            $dto->method('isExpired')->willReturn(true);
            $this->writeObjectToCacheFile($key, $dto);
        }
        $this->assertNotEmpty($this->fileCacheService->getCachedKeys());

        $fileCacheService = new FileCacheService(
            kernel: $this->getKernelMockWithCacheDir($this->cacheDir),
            keyPrefix: '',
            cacheCleanupChanceOneOver: 1,
        );

        $fileCacheService->set('new_key_0', $this->createMock(CacheObjectWrapperDto::class));
        $this->assertEquals($fileCacheService->getCachedKeys(), ['new_key_0']);
    }

    private function getKernelMockWithCacheDir(string $cacheDir): KernelInterface
    {
        $kernelMock = $this->createMock(KernelInterface::class);
        $kernelMock->method('getCacheDir')->willReturn($cacheDir);

        return $kernelMock;
    }

    private function writeObjectToCacheFile(string $key, mixed $object): void
    {
        $filePath = $this->cacheDir . '/file_cache_service/test_prefix-' . $key . '.cache';
        file_put_contents($filePath, serialize($object));
    }

}
