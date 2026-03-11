<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\ArgParser;

#[CoversClass(ArgParser::class)]
class ArgParserTest extends TestCase
{
    #[DataProvider('provideArgvArrays')]
    public function testParseArgvArray(array $argvArrayIn, array $expectedResult): void
    {
        $result = ArgParser::parseArgvArray($argvArrayIn);
        $this->assertSame($expectedResult, $result);
    }

    public function testParserReturn(): void
    {
        $result = ArgParser::parse();
        $this->assertIsArray($result);
    }


    public static function provideArgvArrays(): Generator
    {
        $options = [
            [
                '--key=value',
                ['key' => 'value'],
            ],
            [
                '--flag',
                ['flag' => true],
            ],
            [
                '--key=value=with=equals',
                ['key' => 'value=with=equals'],
            ],
            [
                '--empty=',
                ['empty' => ''],
            ],
            [
                '--',
                [],
            ],
            [
                '--no-value',
                ['no-value' => true],
            ],
            [
                '--mixed-case=Value',
                ['mixed-case' => 'Value'],
            ],
            [
                '--multiple=equals=signs=in=value',
                ['multiple' => 'equals=signs=in=value'],
            ],
            [
                '--only-key',
                ['only-key' => true],
            ],
            [
                '--key-with-dash=value-with-dash',
                ['key-with-dash' => 'value-with-dash'],
            ],
            [
                '--a/WeirdÖne=weirdValue',
                ['a/weirdöne' => 'weirdValue'],
            ],
            [
                '--!"§$&%/())=?`',
                ['!"§$&%/())' => '?`'],
            ],
            [
                '--double--dash=value',
                ['double--dash' => 'value'],
            ],
            [
                '--double--dash-no-value',
                ['double--dash-no-value' => true],
            ]
        ];

        foreach ($options as $index => [$argvArrayIn, $expectedResult]) {
            yield "single test case $index: $argvArrayIn" => [
                'argvArrayIn' => ['script.php', $argvArrayIn],
                'expectedResult' => $expectedResult,
            ];
        }

        foreach ($options as $index => [$argvArrayIn, $expectedResult]) {
            // Generate random test cases with two options
            $randomOption2 = $options[array_rand($options, 1)];

            $arrayIn = ['script.php', $argvArrayIn, $randomOption2[0]];
            $expectedResultCombined = array_merge($expectedResult, $randomOption2[1]);

            yield "two arg test case $index: $argvArrayIn" => [
                'argvArrayIn' => $arrayIn,
                'expectedResult' => $expectedResultCombined,
            ];
        }

        for ($i = 0; $i < 20; $i++) {
            // Generate random combined test cases
            $argvArrayIn = ['script.php'];
            $expectedResult = [];
            $combineOptions = array_rand($options, rand(2, 5));
            for ($j = 0; $j < count($combineOptions); $j++) {
                $randomOption = $options[$combineOptions[$j]];

                $argvArrayIn[] = $randomOption[0];
                $expectedResult = array_merge($expectedResult, $randomOption[1]);
            }

            yield "random combined test case $i" => [
                'argvArrayIn' => $argvArrayIn,
                'expectedResult' => $expectedResult,
            ];
        }
    }
}
