<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;
use Attribute;
use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

#[Attribute(Attribute::TARGET_METHOD)]


class MlcCachableMethod
{
    /**
     * @var callable|null
     */
    private mixed $keyGeneratorCallable = null;

    public function __construct(
        private int $ttlSeconds,
        ?callable $keyGenerator = null,
    ) {
        if ($ttlSeconds <= 0) {
            throw new InvalidArgumentException('TTL must be a positive integer.');
        }
        if ($keyGenerator !== null) {
            self::validateCallableKeyGeneratorSignature($keyGenerator);
        }

        $this->keyGeneratorCallable = $keyGenerator;
    }

    public function getTtlSeconds(): int
    {
        return $this->ttlSeconds;
    }

    public function hasKeyGenerator(): bool
    {
        return $this->keyGeneratorCallable !== null;
    }

    public function getKeyGeneratorCallable(): ?callable
    {
        if ($this->hasKeyGenerator() === false) {
            return null;
        }

        return $this->keyGeneratorCallable;
    }

    /**
     * Validates that the provided key generator callable has the correct signature.
     * @param mixed $keyGenerator
     * @throws InvalidArgumentException
     * @return void
     */
    private static function validateCallableKeyGeneratorSignature(callable $keyGenerator): void
    {
        if (!is_callable($keyGenerator)) {
            throw new InvalidArgumentException('Key generator must be a callable or null.');
        }

        try {
            if ($keyGenerator instanceof Closure) {
                $reflection = new ReflectionFunction($keyGenerator);
            } elseif (is_array($keyGenerator)) {
                $reflection = new ReflectionMethod($keyGenerator[0], $keyGenerator[1]);
            } elseif (is_string($keyGenerator) && strpos($keyGenerator, '::') !== false) {
                [$class, $method] = explode('::', $keyGenerator, 2);
                $reflection = new ReflectionMethod($class, $method);
            } elseif (is_object($keyGenerator)) {
                $reflection = new ReflectionMethod($keyGenerator, '__invoke');
            } else {
                $reflection = new ReflectionFunction($keyGenerator);
            }
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException('Unable to reflect the provided key generator callable.', 0, $e);
        }

        $params = $reflection->getParameters();
        if (count($params) !== 1) {
            throw new InvalidArgumentException('Key generator must accept exactly one parameter of type MethodCallObject.');
        }

        $paramType = $params[0]->getType();
        if (!$paramType instanceof ReflectionNamedType || $paramType->isBuiltin()) {
            throw new InvalidArgumentException('Key generator parameter must be a class type MethodCallObject.');
        }

        $typeName = $paramType->getName();
        if (!str_ends_with($typeName, 'MethodCallObject')) {
            throw new InvalidArgumentException('Key generator parameter must be of type MethodCallObject.');
        }
    }

}
