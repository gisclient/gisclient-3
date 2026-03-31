<?php

declare(strict_types=1);

namespace GisClient\Author\Command;

use GisClient\Author\Api\Service\FontImportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FontImportCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('gisclient:font:import')
            ->setDescription('Imports fonts from a JSON export file (produced by gisclient:font:export)')
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

        /** @var FontImportService $importService */
        $importService = \GCApp::getContainer()->get(FontImportService::class);

        $result = $importService->import($document);

        $output->writeln(sprintf(
            '<info>Imported %d font(s).</info>',
            $result['fonts_imported']
        ));

        return 0;
    }
}
