<?php

declare(strict_types=1);

namespace GisClient\Author\Command;

use GisClient\Author\Api\Service\DataImportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataImportCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('gisclient:data:import')
            ->setDescription('Imports non-project entities from a JSON export file (format 1.0 or 2.0)')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the JSON export file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');
        if (!is_file($file)) {
            $output->writeln(sprintf('<error>File not found: %s</error>', $file));

            return 1;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            $output->writeln(sprintf('<error>Cannot read file: %s</error>', $file));

            return 1;
        }

        $document = json_decode($content, true);
        if (!is_array($document)) {
            $output->writeln('<error>Invalid JSON in export file.</error>');

            return 1;
        }

        /** @var DataImportService $importService */
        $importService = \GCApp::getContainer()->get(DataImportService::class);

        $counts = $importService->import($document);

        foreach ($counts as $key => $count) {
            $output->writeln(sprintf('<info>%s: %d</info>', $key, $count));
        }

        return 0;
    }
}
