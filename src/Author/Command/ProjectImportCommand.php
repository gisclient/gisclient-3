<?php

declare(strict_types=1);

namespace GisClient\Author\Command;

use GisClient\Author\Api\Gateway\ApiCrudGatewayInterface;
use GisClient\Author\Api\Service\ProjectImportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectImportCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('gisclient:project:import')
            ->setDescription('Imports a project from a JSON export file')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the JSON export file')
            ->addOption('project-name', null, InputOption::VALUE_REQUIRED, 'Override the target project name from the file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');
        $projectNameOverride = $input->getOption('project-name');

        if (!file_exists($file)) {
            $output->writeln("<error>File not found: $file</error>");
            return 1;
        }

        $content = file_get_contents($file);
        $document = json_decode($content, true);
        if (!is_array($document)) {
            $output->writeln('<error>Invalid JSON in import file</error>');
            return 1;
        }

        $targetProjectName = $projectNameOverride ?? ($document['project_name'] ?? null);
        if ($targetProjectName === null) {
            $output->writeln('<error>Could not determine target project name. Use --project-name to specify it.</error>');
            return 1;
        }

        /** @var ProjectImportService $importService */
        $importService = \GCApp::getContainer()->get(ProjectImportService::class);
        /** @var ApiCrudGatewayInterface $gateway */
        $gateway = \GCApp::getContainer()->get(ApiCrudGatewayInterface::class);

        $output->writeln("<info>Importing project '$targetProjectName'...</info>");
        $importService->importProject($gateway, $document, $targetProjectName);
        $output->writeln("<info>Project '$targetProjectName' imported successfully.</info>");

        return 0;
    }
}
