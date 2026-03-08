<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\PhpDocManipulatorService;

#[CoversClass(PhpDocManipulatorService::class)]
class PhpDocManipulatorServiceTest extends TestCase
{
    public function testAddToEmptyDocComment(): void
    {
        $result = PhpDocManipulatorService::add(false, '@test');
        $expected = "    /**\n     * @test\n     */";
        $this->assertEquals($expected, $result);
    }

    public function testAddToExistingDocComment(): void
    {
        $docComment = "    /**\n     * Existing description.\n     * @param string \$param\n     */";
        $result = PhpDocManipulatorService::add($docComment, '@return void');
        $expected = "    /**\n     * Existing description.\n     * @param string \$param\n     * @return void\n     */";
        $this->assertEquals($expected, $result);
    }

    public function testAddToDescription(): void
    {
        $docComment = "    /**\n     * Existing description.\n     * @param string \$param\n     */";
        $result = PhpDocManipulatorService::add($docComment, 'Additional description.', 'description');
        $expected = "    /**\n     * Existing description.\n     * Additional description.\n     * @param string \$param\n     */";
        $this->assertEquals($expected, $result);
    }

    public function testEmptyIndent(): void
    {
        $result = PhpDocManipulatorService::indent(false, 1);
        $this->assertEquals('', $result);
    }
}
