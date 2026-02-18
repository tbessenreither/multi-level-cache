<?php

declare(strict_types=1);

$vendorAutoload = (function (): string {
    $dir = __DIR__;
    while (true) {
        $candidate = $dir . '/vendor/autoload.php';
        if (is_file($candidate)) {
            return $candidate;
        }
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }

    $fallback = dirname(__DIR__, 4) . '/vendor/autoload.php';
    if (is_file($fallback)) {
        return $fallback;
    }

    throw new RuntimeException('Could not locate vendor/autoload.php. Run composer install.');
})();

$loader = require $vendorAutoload;
$loader->addPsr4('Tbessenreither\\MultiLevelCache\\CachedServiceGenerator\\', __DIR__ . '/..');
