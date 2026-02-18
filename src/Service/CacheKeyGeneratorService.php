<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Service;

class CacheKeyGeneratorService
{
    public const KEY_DELIMITER = ':';

    /**
     * Generates a cache key based on the given IRI and parameters.
     * @param string $iri The IRI to base the cache key on.
     * @param array<string, int|float|string> $parameters The parameters to include in the cache key.
     * @param string|null $prefix An optional prefix to prepend to the cache key.
     * @return string The generated cache key.
     */
    public static function fromIri(string $iri, array $parameters, ?string $prefix = null): string
    {
        ksort($parameters);
        $paramString = http_build_query($parameters);

        $iriCleaned = trim($iri, '/');
        $iriCleaned = str_replace(['http://', 'https://', 'www.'], '', $iriCleaned);
        $iriCleaned = str_replace(self::KEY_DELIMITER, '_', $iriCleaned);
        $iriCleaned = str_replace(['/'], self::KEY_DELIMITER, $iriCleaned);

        $cacheKeyParts = [];
        if ($prefix !== null) {
            $cacheKeyParts[] = trim($prefix, self::KEY_DELIMITER);
        }
        $cacheKeyParts[] = $iriCleaned;
        $cacheKeyParts[] = md5($paramString);

        return implode(self::KEY_DELIMITER, $cacheKeyParts);
    }

}
