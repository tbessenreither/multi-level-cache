<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCachedService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Exception\MlcUpdateCachedServiceException;
use Throwable;

class MakeCachedServiceService
{
    public const int DEFAULT_TTL_SECONDS = 3600;

    public function __construct(
    ) {
    }

    /**
     * @return array<{interface: string, class: string}>
     */
    public function generateCachedService(string $class, ?string $cachedClass = null): array
    {
        $reflection = new ReflectionClass($class);

        $classDotSeparated = str_replace('\\', '.', $class); # Used for CLI notation
        $classUnderscoreSeparated = str_replace('\\', '_', $class); # used for cache key notation
        $classHyphenSeparated = str_replace('\\', '-', $class); # used for cache group notation

        $attribute = $reflection->getAttributes(MlcCachedService::class);
        if (!empty($attribute)) {
            throw new RuntimeException("The class '{$class}' is already a cached service.");
        }

        $mlcCacheableServiceAttribute = MlcCacheableService::fromReflectionClass($reflection);

        $namespace = $reflection->getNamespaceName();
        $shortName = $reflection->getShortName();

        if ($cachedClass === null) {
            $cachedClassName = $shortName . 'Cached';
            $cachedClass = $namespace . '\\' . $cachedClassName;
        } else {
            $cachedClassName = $cachedClass;
            if (str_contains($cachedClass, '\\')) {
                // fully qualified class name
                $parts = explode('\\', $cachedClass);
                $cachedClassName = array_pop($parts);
            }
        }

        $this->checkOriginalClass($class);
        $this->checkCachedClass($cachedClass);

        $methods = $this->getPublicMethods($reflection);

        $rootInfo = FileOperationService::findRootForClass($class);

        $interfaceRootNamespace = $rootInfo['namespace'] . 'Interface\\';
        $interfaceRelativeNamespace = str_replace(
            $rootInfo['namespace'],
            '',
            $namespace
        );
        $interfaceNamespace = $interfaceRootNamespace . $interfaceRelativeNamespace;
        $interfaceClassName = $shortName . 'Interface';
        $interfaceClass = $interfaceNamespace . '\\' . $interfaceClassName;

        $useLines = $this->getCleanedUseLinesForReflection(
            reflection: $reflection,
            excludeUseStrings: array_merge(
                $this->getUseLinesFromFile(file: __DIR__ . '/' . RenderTemplateService::TEMPLATE_DIRECTORY . '/Class/CachedServiceTemplate.txt'),
                [
                    "use {$interfaceClass};"
                ]
            )
        );
        $useLines = array_unique($useLines);
        sort($useLines);

        $dynamicMethods = $this->generateDynamicMethodsCode($methods, $useLines);

        $this->checkInterfaceFile(originalClass: $class, interfaceClass: $interfaceClass);

        $interfaces = [
            $interfaceClassName,
        ];
        if ($mlcCacheableServiceAttribute->getAdditionalInterface() !== null) {
            $useLines[] = "use {$mlcCacheableServiceAttribute->getAdditionalInterface()};";
            $interfaces[] = $this->cleanupFqcnBasedOnUseLines($useLines, '\\' . $mlcCacheableServiceAttribute->getAdditionalInterface());
        }

        $interfaceCode = RenderTemplateService::render('Interface/InterfaceWrapper', [
            'InterfaceNamespace' => $interfaceNamespace,
            'ServiceName' => $shortName,
            'UseLines' => implode(PHP_EOL, $useLines),
            'DynamicInterfaceSignatures' => $dynamicMethods['interfaces'],
        ]);

        $generatedFiles = [];

        $generatedFiles['interface'] = FileOperationService::writeFile(
            originalClass: $class,
            class: $interfaceClass,
            code: $interfaceCode
        );

        $useLines[] = "use {$interfaceClass};";
        sort($useLines);

        $classCode = RenderTemplateService::render('Class/CachedService', [
            'ServiceNamespace' => $namespace,
            'ServiceName' => $shortName,
            'ClassDotSeparated' => $classDotSeparated,
            'ClassUnderscoreSeparated' => $classUnderscoreSeparated,
            'ClassHyphenSeparated' => $classHyphenSeparated,
            'UseLines' => implode(PHP_EOL, $useLines),
            'InterfacesString' => implode(', ', $interfaces),
            'DefaultTtlSeconds' => self::DEFAULT_TTL_SECONDS,
            'DynamicMethods' => $dynamicMethods['methods'],
        ]);

        $generatedFiles['class'] = FileOperationService::writeFile(
            originalClass: $class,
            class: $cachedClass,
            code: $classCode,
        );

        FileOperationService::addInterfaceToClass(
            class: $class,
            interface: $interfaceClass,
        );

        return $generatedFiles;
    }

    /**
     *
     * Updates all cached services that are marked as autogenerated.
     * @return array<array{service: string, status: string, message: string}>
     */
    public function updateAllCachedServices(): array
    {
        $updatedServices = [];
        foreach (FetchAllCachedServices::getAllAutogeneratedServices() as $serviceData) {
            try {
                $serviceName = $serviceData['serviceName'];

                $getOriginalClass = $serviceData['cachedServiceAttr']->getOriginalServiceClass();
                $this->generateCachedService(
                    class: $getOriginalClass,
                    cachedClass: $serviceData['reflection']->getName(),
                );

                $updatedServices[] = [
                    'service' => $serviceName,
                    'status' => 'updated',
                    'message' => '',
                ];
            } catch (MlcUpdateCachedServiceException $e) {
                $updatedServices[] = [
                    'service' => $serviceName,
                    'status' => $e->getStatus(),
                    'message' => $e->getMessage(),
                ];
            } catch (Throwable $e) {
                $updatedServices[] = [
                    'service' => $serviceName,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $updatedServices;
    }

    /**
     * Generate PHP code for all dynamic methods.
     *
     * @param ReflectionMethod[] $classMethods
     * @param string[] $useLines
     * @return array{methods: string, interfaces: string}
     */
    private function generateDynamicMethodsCode(array $classMethods, array $useLines): array
    {
        // Get all public methods from the original service class
        $serviceClass = $classMethods ? $classMethods[0]->getDeclaringClass()->getName() : null;
        $serviceClassShort = $classMethods ? $classMethods[0]->getDeclaringClass()->getShortName() : null;
        if (!$serviceClass) {
            throw new RuntimeException("Could not determine service class from methods.");
        }

        $methods = [];
        $interfaceSignatures = [];

        $methodDoc = false;
        foreach ($classMethods as $method) {
            $mlcCacheableMethodAttribute = MlcCacheableMethod::fromReflectionMethod(method: $method, throw: false);
            $name = $method->getName();
            // Filter out all magic methods (names starting with '__')
            if ($method->isConstructor() || str_starts_with($name, '__')) {
                continue;
            }
            $params = [];
            $args = [];

            $parameters = $method->getParameters();

            if ($mlcCacheableMethodAttribute?->getBulkConfig() !== null) {
                if (count($parameters) === 0) {
                    throw new RuntimeException("Method {$method->getName()} is configured as bulk method but has no parameters. Bulk methods must have at least one parameter of type string[] to work with CSG.");
                }
                if (!$parameters[0]->hasType() || $parameters[0]->getType()->__toString() !== 'array') {
                    throw new RuntimeException("Method {$method->getName()} is configured as bulk method but the first parameter does not have type string[]. Found type " . ($parameters[0]->hasType() ? $parameters[0]->getType()->__toString() : 'none') . ".");
                }
                if ($method->getReturnType() === null || $method->getReturnType()->__toString() !== 'array') {
                    throw new RuntimeException("Method {$method->getName()} is configured as bulk method but does not have return type array. Found type " . ($method->getReturnType() ? $method->getReturnType()->__toString() : 'none') . ".");
                }
            }

            foreach ($parameters as $param) {
                $paramStr = '';

                if ($param->hasType()) {
                    $types = [];
                    $typeConcat = '&';
                    $type = $param->getType();
                    if ($type instanceof ReflectionNamedType) {
                        $types = [$type];
                    } elseif ($type instanceof ReflectionUnionType) {
                        $types = $type->getTypes();
                        $typeConcat = '|';
                    } elseif ($type instanceof ReflectionIntersectionType) {
                        $types = $type->getTypes();
                        $typeConcat = '&';
                    } else {
                        throw new RuntimeException("Unsupported parameter type for parameter \${$param->getName()} in method {$method->getName()}.");
                    }

                    $unionTypeStrings = [];
                    foreach ($types as $unionType) {
                        if (!$unionType instanceof ReflectionNamedType) {
                            throw new RuntimeException("Unsupported parameter type for parameter \${$param->getName()} in method {$method->getName()}. Only named types are supported in union/intersection types.");
                        }
                        $unionTypeStr = '';
                        if ($unionType->allowsNull()) {
                            $unionTypeStr .= '?';
                        }
                        $unionTypeStr .= $this->fixInlinedClassNames($useLines, $unionType->getName());
                        $unionTypeStrings[] = $unionTypeStr;
                    }
                    $paramStr .= implode($typeConcat, $unionTypeStrings) . ' ';
                }
                $paramStr .= '$' . $param->getName();
                if ($param->isDefaultValueAvailable()) {
                    $default = var_export($param->getDefaultValue(), true);
                    if (str_starts_with($default, 'array (') && str_ends_with($default, ')')) {
                        $default = '[' . substr($default, strlen('array ('), -1) . ']';
                    }
                    if ($default === "[\n]") {
                        $default = '[]';
                    }
                    $paramStr .= ' = ' . $default;
                }
                $params[] = $paramStr;
                $args[] = '$' . $param->getName();
            }
            $returnType = $method->getReturnType();
            $returnTypeStr = '';
            if ($returnType && $returnType instanceof ReflectionNamedType) {
                if ($returnType->allowsNull()) {
                    $returnTypeStr .= '?';
                }
                $returnTypeStr .= $this->fixInlinedClassNames($useLines, $returnType->getName());
            } else {
                throw new RuntimeException("Method {$method->getName()} is missing a return type declaration. This is a requirement for cached services.");
            }
            $isVoid = $returnTypeStr === 'void';

            // Get doc comment
            $methodDocOriginal = $method->getDocComment();

            $isCached = false;
            $cacheTtl = 0;
            if ($mlcCacheableMethodAttribute !== null) {
                $isCached = true;
                $cacheTtl = $mlcCacheableMethodAttribute->getTtlSeconds();
                $methodDoc = PhpDocManipulatorService::add(
                    docComment: $methodDocOriginal,
                    linesToAdd: "cache-ttl should be {$cacheTtl} seconds. Check Attribute in {$serviceClass} for details.",
                    position: 'description',
                );
            }

            if ($isCached && !$isVoid) {
                $templateName = 'CacheServiceCachedMethod';
            } else {
                if ($method->isStatic()) {
                    $templateName = 'CacheServiceUncachedStaticMethod';
                } else {
                    $templateName = 'CacheServiceUncachedMethod';
                }
            }

            $methods[] = RenderTemplateService::render('Class/' . $templateName, [
                'ServiceName' => $serviceClassShort,
                'MethodDocumentation' => PhpDocManipulatorService::indent($methodDoc, 1),
                'MethodFunctionToken' => $method->isStatic() ? 'static function' : 'function',
                'MethodName' => $method->getName(),
                'MethodNamePostfix' => $method->isStatic() ? 'Cached' : '',
                'MethodArguments' => implode(', ', $params),
                'MethodReturnType' => $returnTypeStr,
                'MethodArgumentsArray' => implode(', ', $args),
                'IsStaticBoolean' => $method->isStatic() ? 'true' : 'false',
                'MethodReturnStatement' => $returnTypeStr !== 'void' ? 'return ' : '',
                'CallCachedMethod' => $mlcCacheableMethodAttribute?->getBulkConfig() === null ? 'callCachedMethod' : 'callCachedBulkMethod',
            ]);

            $interfaceSignatures[] = RenderTemplateService::render('Interface/Signature', [
                'ServiceName' => $serviceClassShort,
                'MethodDocumentation' => PhpDocManipulatorService::indent($methodDocOriginal),
                'MethodFunctionToken' => $method->isStatic() ? 'static function' : 'function',
                'MethodName' => $method->getName(),
                'MethodArguments' => implode(', ', $params),
                'MethodReturnType' => $returnTypeStr,
                'MethodArgumentsArray' => implode(', ', $args),
                'MethodReturnStatement' => $returnTypeStr !== 'void' ? 'return ' : '',
            ]);

            if ($isCached && $method->isStatic()) {
                // For static cached methods, also generate a non-cached version

                $methodDocStatic = PhpDocManipulatorService::add(
                    docComment: $methodDocOriginal,
                    linesToAdd: [
                        "WARNING: Static methods can't be cached.",
                        "This is the uncached version of the method.",
                        "",
                        "Consider using the non static {$method->getName()}Cached() method for better performance but be aware of the fact it's not available in {$serviceClassShort}."
                    ],
                    position: 'description',
                );
                $methodDocStatic = PhpDocManipulatorService::add(
                    docComment: $methodDocStatic,
                    linesToAdd: [
                        "@see {$method->getName()}Cached().",
                    ],
                    position: '@',
                );
                $methods[] = RenderTemplateService::render('Class/CacheServiceUncachedStaticMethod', [
                    'ServiceName' => $serviceClassShort,
                    'MethodDocumentation' => PhpDocManipulatorService::indent($methodDocStatic),
                    'MethodFunctionToken' => 'static function',
                    'MethodName' => $method->getName(),
                    'MethodNamePostfix' => '',
                    'MethodArguments' => implode(', ', $params),
                    'MethodReturnType' => $returnTypeStr,
                    'MethodArgumentsArray' => implode(', ', $args),
                    'IsStaticBoolean' => 'true',
                    'MethodReturnStatement' => $returnTypeStr !== 'void' ? 'return ' : '',
                ]);
            }

        }

        $methodCode = implode("\n", $methods);
        $interfaceCode = implode("\n", $interfaceSignatures);

        return [
            'methods' => $methodCode,
            'interfaces' => $interfaceCode,
        ];
    }

    /**
     * @return array<ReflectionMethod>
     */
    private function getPublicMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[] = $method;
        }

        return $methods;
    }

    private function fixInlinedClassNames(array $useLines, string $typeString): string
    {
        if (str_contains($typeString, '\\') && !in_array($typeString, ['int ', 'float ', 'string ', 'bool ', 'array ', 'callable ', 'iterable ', 'object ', 'mixed ', 'void '], true)) {
            $typeString = '\\' . $typeString;
            $typeString = $this->cleanupFqcnBasedOnUseLines($useLines, $typeString);
        }

        return $typeString;
    }

    private function checkOriginalClass(string $originalClass): void
    {
        if (!class_exists($originalClass)) {
            throw new RuntimeException("Original service class '{$originalClass}' does not exist.");
        }
    }

    private function checkCachedClass(string $cachedClass): void
    {
        if (!class_exists($cachedClass)) {
            return;
        }

        $reflection = new ReflectionClass($cachedClass);
        $attribute = $reflection->getAttributes(MlcCachedService::class);
        if (empty($attribute)) {
            throw new RuntimeException("Cached service class '{$cachedClass}' is not marked with MlcCachedService attribute.");
        }

        $attributeInstance = $attribute[0]->newInstance();
        if (!class_exists($attributeInstance->getOriginalServiceClass())) {
            throw new RuntimeException("Cached service class '{$cachedClass}' does not reference an existing service class.");
        }

        if (!$attributeInstance->isSyncAllowed()) {
            throw new MlcUpdateCachedServiceException(
                type: 'info',
                status: 'skipped',
                message: "Cached service class '{$cachedClass}' is marked as user modified (syncAllowed: false).",
            );
        }
    }

    private function checkInterfaceFile(string $originalClass, string $interfaceClass): void
    {
        $interfaceFilePath = FileOperationService::getFilePathFromClassString(
            originalClass: $originalClass,
            class: $interfaceClass,
        );

        if (!file_exists($interfaceFilePath)) {
            return;
        }

        $interfaceContent = file_get_contents($interfaceFilePath);

        if (str_contains($interfaceContent, 'Autogenerated by MLC CachedServiceGenerator')) {
            return;
        }

        throw new RuntimeException("Interface file for '{$interfaceClass}' already exists and is not marked as autogenerated. Please remove or rename the existing file to allow the generator to create the interface.");

    }

    private function getUseLinesFromFile(string $file, array $excludeUseStrings = []): array
    {
        $fileContent = file_get_contents($file);
        if ($fileContent === false) {
            return [];
        }

        $useLines = [];
        $lines = explode("\n", $fileContent);
        foreach ($lines as $line) {
            $lineTrimmed = trim($line);
            if (
                str_starts_with($lineTrimmed, 'use ')
                && str_ends_with($lineTrimmed, ';')
                && !in_array($lineTrimmed, $excludeUseStrings, true)
            ) {
                $useLines[] = $lineTrimmed;
            } elseif (
                str_starts_with($lineTrimmed, 'class')
                || str_starts_with($lineTrimmed, 'interface')
                || str_starts_with($lineTrimmed, 'function')
            ) {
                // stop parsing after class declaration
                break;
            }
        }

        $useLines = array_unique($useLines);
        sort($useLines);

        return $useLines;
    }

    private function getCleanedUseLinesForReflection(ReflectionClass $reflection, array $excludeUseStrings = []): array
    {
        $file = $reflection->getFileName();
        if ($file === false) {
            return [];
        }
        $test = 'concatmoretext';

        return $this->getUseLinesFromFile($file, $excludeUseStrings);
    }

    /**
     * @param string[] $useLines
     */
    private function cleanupFqcnBasedOnUseLines(array $useLines, string $fqcn): string
    {
        $cleanedFqcn = $fqcn;
        foreach ($useLines as $useLine) {
            $strStartPattern = 'use ' . ltrim($fqcn, '\\');
            if (
                str_starts_with($useLine, $strStartPattern . ' ')
                || str_starts_with($useLine, $strStartPattern . ';')
            ) {
                $cleanedLine = substr($useLine, strlen('use '), -1);
                $trimmedFqcn = trim($cleanedLine);

                if (str_contains($trimmedFqcn, ' as ')) {
                    $fqcnParts = explode(' as ', $trimmedFqcn);
                    $cleanedFqcn = trim(array_pop($fqcnParts));
                } else {
                    $fqcnParts = explode('\\', $trimmedFqcn);
                    $cleanedFqcn = trim(array_pop($fqcnParts));
                }

                break;
            }
        }

        return $cleanedFqcn;
    }
}
