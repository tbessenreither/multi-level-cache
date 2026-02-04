<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Command;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\MlcMakeCachedServiceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mlc:make-cached-service',
    description: 'Create or recreate a cached service wrapper for a given service. You can replace the \\ in the service name with . to represent namespace separators.',
)]
class MlcMakeCachedServiceCommand extends Command
{
    public function __construct(
        private MlcMakeCachedServiceService $mlcMakeCachedServiceService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('service', InputArgument::REQUIRED, 'The service including Namespace to genearte a cached version for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $service = $input->getArgument('service');
        $service = str_replace('.', '\\', $service);

        $serviceClass = $this->resolveServiceClass($service);
        if (!$serviceClass || !class_exists($serviceClass)) {
            $io->error("Service class '$serviceClass' not found.");
            return Command::FAILURE;
        }

        $targetFile = $this->mlcMakeCachedServiceService->generateCachedService($serviceClass);

        $io->success("Cached service class generated: {$targetFile['class']}");
        return Command::SUCCESS;
    }

    private function resolveServiceClass(string $service): ?string
    {
        // Try FQCN first
        if (class_exists($service)) {
            return $service;
        }
        // Try App\\Service namespace
        $fqcn = 'App\\Service\\' . $service;
        if (class_exists($fqcn)) {
            return $fqcn;
        }
        return null;
    }
}
