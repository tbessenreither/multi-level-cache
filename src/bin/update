<?php

declare(strict_types=1);

require_once __DIR__ . '/AutoloadShenanigans.php';

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\MakeCachedServiceService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\PrintTools;

PrintTools::headline('Updating all cached services...');


PrintTools::subHeadline('Starting update process for cached services...');
$service = new MakeCachedServiceService();
$result = $service->updateAllCachedServices();

PrintTools::table(
    ['Service', 'Status', 'Message'],
    $result,
);

PrintTools::success("Cached services updated. See output for details.");
