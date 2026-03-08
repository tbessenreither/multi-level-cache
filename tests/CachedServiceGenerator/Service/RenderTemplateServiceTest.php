<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\RenderTemplateService;

#[CoversClass(RenderTemplateService::class)]
class RenderTemplateServiceTest extends TestCase
{
    public function testRenderReplacements(): void
    {
        $templateName = 'Test';

        $placeholders = [
            'var1' => 'var1-content',
            'var2' => 'var2-content',
            'string' => 'string-value',
            'int' => 15,
            'float' => 1.5,
            'bool-true' => true,
            'bool-false' => false,
            'null' => null,
        ];

        $expectedResult = trim(file_get_contents(__DIR__ . '/RenderTestFiles/' . $templateName . 'Rendered.txt'));

        $renderedTemplate = RenderTemplateService::render($templateName, $placeholders, __DIR__ . '/RenderTestFiles/');
        $renderedTemplate = trim($renderedTemplate);
        $this->assertEquals($expectedResult, $renderedTemplate);
    }

    public function testRenderDefaultTemplateDirectory(): void
    {
        $renderedTemplate = RenderTemplateService::render('Class/CachedService', []);
        $this->assertIsString($renderedTemplate);
    }

    public function testExceptionOnNonExistentTemplate(): void
    {
        $this->expectException(RuntimeException::class);
        $renderedTemplate = RenderTemplateService::render('NonExistentTemplate.txt', []);
    }
}
