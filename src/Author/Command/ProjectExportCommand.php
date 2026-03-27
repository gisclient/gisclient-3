<?php

declare(strict_types=1);

namespace GisClient\Author\Command;

use GisClient\Author\Api\Gateway\ApiCrudGatewayInterface;
use GisClient\Author\Api\Service\ProjectExportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectExportCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('gisclient:project:export')
            ->setDescription('Exports a project and its full entity graph to a JSON file')
            ->addArgument('project-name', InputArgument::REQUIRED, 'Name of the project to export')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path (default: stdout)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $input->getArgument('project-name');
        $outputFile = $input->getOption('output');

        /** @var ProjectExportService $exportService */
        $exportService = \GCApp::getContainer()->get(ProjectExportService::class);
        /** @var ApiCrudGatewayInterface $gateway */
        $gateway = \GCApp::getContainer()->get(ApiCrudGatewayInterface::class);

        $document = $exportService->exportProject($gateway, $projectName);
        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($outputFile !== null) {
            file_put_contents($outputFile, $json);
            $output->writeln("<info>Project '$projectName' exported to $outputFile</info>");
        } else {
            $output->write($json);
        }

        return 0;
    }
}
