<?php

declare(strict_types=1);

namespace GisClient\Author\Command;

use GisClient\Author\Api\Service\DataExportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DataExportCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('gisclient:data:export')
            ->setDescription('Exports non-project entities (symbols, fonts, users, groups) to a single JSON file')
            ->addOption(
                'only',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sections to export: symbols, fonts, users, groups (default: all)',
                []
            )
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path (default: stdout)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var DataExportService $exportService */
        $exportService = \GCApp::getContainer()->get(DataExportService::class);

        /** @var array<int,string> $only */
        $only = $input->getOption('only');

        $validSections = ['symbols', 'fonts', 'users', 'groups'];
        foreach ($only as $section) {
            if (!in_array($section, $validSections, true)) {
                $output->writeln(sprintf(
                    '<error>Unknown section "%s". Valid sections: %s</error>',
                    $section,
                    implode(', ', $validSections)
                ));

                return 1;
            }
        }

        $document = $exportService->export($only);
        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $outputFile = $input->getOption('output');
        if ($outputFile !== null) {
            file_put_contents($outputFile, $json);
            $output->writeln('<info>Data exported to ' . $outputFile . '</info>');
        } else {
            $output->write($json);
        }

        return 0;
    }
}
