<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Commands;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class UpdateTest extends TestCase
{
    public function testUpdate(): void
    {
        $makePath = realpath(__DIR__.'/../../src/Commands/update.php');
        $this->assertNotFalse($makePath);

        $command = sprintf('php %s 2>&1', $makePath);
        exec($command, $output, $resultCode);
        $this->assertSame(0, $resultCode, implode("\n", $output));

        $outputString = implode(PHP_EOL, $output);

        $this->assertStringContainsString('Cached services updated', $outputString);



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
