<?php

declare(strict_types=1);

require_once __DIR__ . '/AutoloadShenanigans.php';

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\ArgParser;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\MakeCachedServiceService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\PrintTools;

PrintTools::headline('Updating all cached services...');

PrintTools::info('Parsing command line arguments...');
$arguments = ArgParser::parse();
PrintTools::table(
    ['Argument', 'Value'],
    array_map(
        fn ($key, $value) => [$key, $value],
        array_keys($arguments),
        $arguments,
    ),
);
if (!isset($arguments['source-dir'])) {
    $arguments['source-dir'] = null;
}
echo PHP_EOL . PHP_EOL;

PrintTools::subHeadline('Starting update process for cached services...');
$service = new MakeCachedServiceService();
$result = $service->updateAllCachedServices($arguments['source-dir']);

PrintTools::table(
    ['Service', 'Status', 'Message'],
    $result,
);

PrintTools::success("Cached services updated. See output for details.");
