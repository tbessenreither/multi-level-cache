<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Service\Implementations;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Exception\CacheEntryCorruptedException;
use Tbessenreither\MultiLevelCache\Exception\CacheEntryExpiredException;
use Tbessenreither\MultiLevelCache\Exception\CacheMissException;
use Tbessenreither\MultiLevelCache\Interface\CacheInformationInterface;
use Tbessenreither\MultiLevelCache\Interface\MultiLevelCacheImplementationInterface;

class FileCacheService implements MultiLevelCacheImplementationInterface, CacheInformationInterface
{
    private string $kernelCacheDir;

    /**
     *
     * @param positive-int $cacheCleanupChanceOneOverix
     */
    public function __construct(
        private KernelInterface $kernel,
        private string $keyPrefix,
        private int $cacheCleanupChanceOneOver = 50,
        private bool $randomCleanupInSet = true,
    ) {
        if ($this->cacheCleanupChanceOneOver < 1) {
            throw new InvalidArgumentException('cacheCleanupChanceOneOver must be a positive integer greater than 0.');
        }

        $this->kernelCacheDir = $this->kernel->getCacheDir();
    }

    public function __destruct()
    {
        $this->removeExpiredCacheFiles();
    }

    public function set(string $key, CacheObjectWrapperDto $object): void
    {
        file_put_contents($this->getCacheFilePath($key), serialize($object));
        if ($this->randomCleanupInSet && rand(1, $this->cacheCleanupChanceOneOver) === 1) {
            $this->removeExpiredCacheFiles();
        }
    }

    public function get(string $key): ?CacheObjectWrapperDto
    {
        try {
            $filePath = $this->getCacheFilePath($key);

            if (!file_exists($filePath)) {
                throw new CacheMissException('Cache entry not found.');
            }

            $serializedData = file_get_contents($filePath);
            if ($serializedData === false) {
                throw new CacheEntryCorruptedException('Cache entry is corrupted or invalid. Could not read file.');
            }

            $object = @unserialize($serializedData);
            if (!$object instanceof CacheObjectWrapperDto) {
                throw new CacheEntryCorruptedException('Cache entry is corrupted or invalid. Object not of type ' . CacheObjectWrapperDto::class);
            }

            if ($object->isExpired()) {
                throw new CacheEntryExpiredException('Cache object has expired.');
            }

            return $object;
        } catch (CacheEntryCorruptedException|CacheEntryExpiredException) {
            $this->delete($key);

            return null;
        } catch (Exception) {
            return null;
        }
    }

    public function delete(string $key): void
    {
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            if (!@unlink($filePath)) {
                // Log or handle the failure to delete the file
                throw new RuntimeException(sprintf('Failed to delete cache file: %s', $filePath));
            }
        }
    }

    public function clear(): bool
    {
        $dir = $this->getCacheDirectory();
        $files = scandir($dir);

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'cache') {
                $filePath = $dir . '/' . $file;
                @unlink($filePath);
            }
        }

        return true;
    }

    public function getConfiguration(): array
    {
        return [
            'prefix' => $this->keyPrefix,
            'cacheDir' => $this->getCacheDirectory(),
            'serialization' => 'php_serialize',
        ];
    }

    public function getCachedKeys(): array
    {
        $dir = $this->getCacheDirectory();
        if (!is_dir($dir)) {
            return [];
        }

        $files = scandir($dir);
        $keys = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'cache') {
                $key = pathinfo($file, PATHINFO_FILENAME);
                if (str_starts_with($key, $this->keyPrefix . '-')) {
                    $key = substr($key, strlen($this->keyPrefix) + 1);
                }
                $keys[] = $key;
            }
        }

        return $keys;
    }

    private function getCacheFilePath(string $key): string
    {
        $dir = $this->getCacheDirectory();

        return $dir . '/' . $this->getPrefixedCacheKey($key) . '.cache';
    }

    private function getCacheDirectory(): string
    {
        $dir = $this->kernelCacheDir . '/file_cache_service';
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created.', $dir));
            }
        }

        return $dir;
    }

    private function getPrefixedCacheKey(string $key): string
    {
        $keyTrimmed = trim($key);
        $keyCleaned = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $keyTrimmed); // Sanitize key to allow only alphanumeric, underscore, and hyphen
        if (empty($this->keyPrefix)) {
            return $keyCleaned;
        }

        return $this->keyPrefix . '-' . $keyCleaned;
    }

    private function removeExpiredCacheFiles(): void
    {
        $dir = $this->getCacheDirectory();
        $files = scandir($dir);

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'cache') {
                $filePath = $dir . '/' . $file;
                $serializedData = file_get_contents($filePath);
                if ($serializedData !== false) {
                    $object = unserialize($serializedData);
                    if (($object instanceof CacheObjectWrapperDto && $object->isExpired()) || !$object instanceof CacheObjectWrapperDto) {
                        unlink($filePath);
                    }
                }
            }
        }
    }

}
