<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Command;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\MlcMakeCachedServiceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mlc:update-cached-services',
    description: 'Create or recreate a cached service wrapper for a given service. You can replace the \\ in the service name with . to represent namespace separators.',
)]
class MlcUpdateCachedServicesCommand extends Command
{
    public function __construct(
        private MlcMakeCachedServiceService $mlcMakeCachedServiceService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Updating all existing cached services...');

        $result = $this->mlcMakeCachedServiceService->updateAllCachedServices();
        $io->table(
            ['Service', 'Status', 'Message'],
            $result,
        );

        $io->success("Cached services updated. See output for details.");
        return Command::SUCCESS;
    }
}
