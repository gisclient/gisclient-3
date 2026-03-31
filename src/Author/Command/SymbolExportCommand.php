<?php

declare(strict_types=1);

namespace GisClient\Author\Command;

use GisClient\Author\Api\Service\SymbolExportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SymbolExportCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('gisclient:symbol:export')
            ->setDescription('Exports all symbols (including pixmap binaries) to a JSON file')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path (default: stdout)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var SymbolExportService $exportService */
        $exportService = \GCApp::getContainer()->get(SymbolExportService::class);

        $document = $exportService->export();
        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $outputFile = $input->getOption('output');

        if ($outputFile !== null) {
            file_put_contents($outputFile, $json);
            $output->writeln("<info>Symbols exported to $outputFile</info>");
        } else {
            $output->write($json);
        }

        return 0;
    }
}
