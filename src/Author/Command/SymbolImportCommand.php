<?php

declare(strict_types=1);

namespace GisClient\Author\Command;

use GisClient\Author\Api\Service\SymbolImportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SymbolImportCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('gisclient:symbol:import')
            ->setDescription('Imports symbols from a JSON export file (produced by gisclient:symbol:export)')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the JSON export file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');

        if (!is_file($file)) {
            $output->writeln("<error>File not found: $file</error>");

            return 1;
        }

        $contents = file_get_contents($file);
        $document = json_decode($contents, true);

        if (!is_array($document)) {
            $output->writeln('<error>Invalid JSON file.</error>');

            return 1;
        }

        /** @var SymbolImportService $importService */
        $importService = \GCApp::getContainer()->get(SymbolImportService::class);

        $result = $importService->import($document);

        $output->writeln(sprintf(
            '<info>Imported %d symbol(s), wrote %d pixmap file(s).</info>',
            $result['symbols_imported'],
            $result['pixmap_files_written']
        ));

        return 0;
    }
}
