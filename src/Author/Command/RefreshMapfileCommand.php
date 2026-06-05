<?php

namespace GisClient\Author\Command;

use GisClient\MapServer\Compare\ComparisonResult;
use GisClient\MapServer\Compare\MapfileComparator;
use GisClient\MapServer\Writer\LegacyMapfileWriter;
use GisClient\MapServer\Writer\MapfileDataCache;
use GisClient\MapServer\Writer\MapfileWriterInterface;
use GisClient\MapServer\Writer\OptimizedMapfileWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshMapfileCommand extends Command
{
    private const WRITER_LEGACY = 'legacy';
    private const WRITER_OPTIMIZED = 'optimized';

    protected function configure()
    {
        $this->setName("gisclient:refresh-mapfile")
            ->setDescription("Refreshes a mapfile")
            ->setHelp("This command allows you to refresh a mapfile.")
            ->addArgument(
                "project",
                InputArgument::OPTIONAL,
                "Which project is the mapset a part of?",
                "all"
            )
            ->addArgument(
                "mapset",
                InputArgument::OPTIONAL,
                "What's the name of the mapset to refresh? (Defaults to 'all' when project equals 'all')",
                "all"
            )
            ->addOption(
                "temporary",
                "t",
                InputOption::VALUE_NONE,
                "Only refresh temporary mapfile(s)"
            )
            ->addOption(
                "layer-mapfile",
                "l",
                InputOption::VALUE_NONE,
                "Additionaly create/refresh mapfile for each layer"
            )
            ->addOption(
                "writer",
                "w",
                InputOption::VALUE_REQUIRED,
                "Which mapfile writer to use: 'legacy' or 'optimized'",
                self::WRITER_OPTIMIZED
            )
            ->addOption(
                "compare",
                "c",
                InputOption::VALUE_NONE,
                "Run both writers and report output differences per file (the files on disk are the optimized writer's output afterwards). Ignores --writer."
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getArgument("project");
        $mapset = $input->getArgument("mapset");
        $public = !$input->getOption("temporary");
        $layerMapfile = $input->getOption("layer-mapfile");
        $writerName = $input->getOption("writer");
        $compare = $input->getOption("compare");

        if (!in_array($writerName, [self::WRITER_LEGACY, self::WRITER_OPTIMIZED], true)) {
            throw new \InvalidArgumentException(
                "Unknown writer '$writerName', expected '" . self::WRITER_LEGACY . "' or '" . self::WRITER_OPTIMIZED . "'."
            );
        }

        $targets = $this->resolveTargets($project, $mapset);

        if ($compare) {
            $exitCode = $this->compareTargets($output, $targets, $public, $layerMapfile);
        } else {
            $exitCode = 0;
            $writer = $this->createWriter($writerName);
            foreach ($targets as $target) {
                $this->refreshMapset($output, $writer, $target['project'], $target['mapset'], $public, $layerMapfile);
            }
        }

        $errors = \GCError::get();
        if (!empty($errors)) {
            throw new \Exception("GCErrors: " . implode("\n", $errors));
        }
        if ($output->isVerbose()) {
            $output->writeln("<info>Done.</info>");
        }

        return $exitCode;
    }

    /**
     * Resolve the project/mapset arguments to a list of (project, mapset) pairs.
     *
     * @return array<array{project: string, mapset: string}>
     */
    private function resolveTargets($project, $mapset): array
    {
        $targets = [];
        if ($project === "all") {
            // all mapsets of all projects
            foreach (\GCAuthor::getProjects() as $projectData) {
                foreach (\GCAuthor::getMapsets($projectData["project_name"]) as $mapsetData) {
                    $targets[] = [
                        'project' => $projectData["project_name"],
                        'mapset' => $mapsetData['mapset_name'],
                    ];
                }
            }
        } elseif ($mapset === "all") {
            // all mapsets of specified project
            if (!\GCAuthor::hasProject($project)) {
                throw new \Exception("Project '$project' does not exist.");
            }
            foreach (\GCAuthor::getMapsets($project) as $mapsetData) {
                $targets[] = [
                    'project' => $project,
                    'mapset' => $mapsetData['mapset_name'],
                ];
            }
        } else {
            $targets[] = [
                'project' => $project,
                'mapset' => $mapset,
            ];
        }
        return $targets;
    }

    private function createWriter(string $writerName): MapfileWriterInterface
    {
        if ($writerName === self::WRITER_OPTIMIZED) {
            return new OptimizedMapfileWriter();
        }
        return new LegacyMapfileWriter();
    }

    protected function refreshMapset(
        OutputInterface $output,
        MapfileWriterInterface $writer,
        $project,
        $mapset,
        $public,
        $layerMapfile
    ): void {
        if ($output->isVerbose()) {
            $output->writeln("<info>Refreshing mapset '$mapset' for project '$project'...</info>");
        }
        if (function_exists('Sentry\addBreadcrumb')) {
            \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                \Sentry\Breadcrumb::LEVEL_INFO,
                \Sentry\Breadcrumb::TYPE_DEFAULT,
                'mapfile',
                "Refreshing mapset '$mapset' for project '$project'"
            ));
        }
        $writer->refreshMapset(
            $project,
            $mapset,
            $public,
            $layerMapfile
        );
    }

    /**
     * Run the legacy and the optimized writer for every target and compare
     * the produced files byte by byte.
     *
     * @param array<array{project: string, mapset: string}> $targets
     */
    private function compareTargets(OutputInterface $output, array $targets, $public, $layerMapfile): int
    {
        $comparator = new MapfileComparator();
        $legacyWriter = new LegacyMapfileWriter();
        $optimizedWriter = new OptimizedMapfileWriter();

        $ownTemplateNamesByProject = [];
        $hasUnexpectedDifferences = false;

        foreach ($targets as $target) {
            $project = $target['project'];
            $mapset = $target['mapset'];
            $projectDir = ROOT_PATH . 'map/' . $project;

            $output->writeln("<info>Comparing writers for mapset '$mapset' of project '$project'...</info>");

            $this->refreshMapset($output, $legacyWriter, $project, $mapset, $public, $layerMapfile);
            $legacySnapshot = $comparator->snapshot($projectDir);

            $this->refreshMapset($output, $optimizedWriter, $project, $mapset, $public, $layerMapfile);
            $optimizedSnapshot = $comparator->snapshot($projectDir);

            if (!isset($ownTemplateNamesByProject[$project])) {
                $cache = new MapfileDataCache(\GCApp::getDB());
                $cache->loadTemplateData($project);
                $ownTemplateNamesByProject[$project] = array_map(
                    fn ($row) => $row['layergroup_name'] . '.' . $row['layer_name'],
                    $cache->getTemplateLayers()
                );
            }

            $result = $comparator->compare(
                $legacySnapshot,
                $optimizedSnapshot,
                $ownTemplateNamesByProject[$project]
            );

            $counts = $result->getCounts();
            $output->writeln(sprintf(
                "  identical: %d, differs: %d, only-in-legacy: %d, only-in-optimized: %d, expected-removed: %d",
                $counts[ComparisonResult::IDENTICAL],
                $counts[ComparisonResult::DIFFERS],
                $counts[ComparisonResult::ONLY_IN_LEGACY],
                $counts[ComparisonResult::ONLY_IN_OPTIMIZED],
                $counts[ComparisonResult::EXPECTED_REMOVED]
            ));

            foreach ($result->getVerdicts() as $path => $verdict) {
                if ($verdict === ComparisonResult::IDENTICAL) {
                    continue;
                }
                if ($verdict === ComparisonResult::EXPECTED_REMOVED) {
                    if ($output->isVerbose()) {
                        $output->writeln("  <comment>[$verdict]</comment> $path");
                    }
                    continue;
                }
                $output->writeln("  <error>[$verdict]</error> $path");
            }

            $dumped = $comparator->dumpDifferences(
                $result,
                $legacySnapshot,
                $optimizedSnapshot,
                ROOT_PATH . 'map/.compare/' . $project
            );
            if (!empty($dumped)) {
                $output->writeln("  differing file pairs saved under " . ROOT_PATH . 'map/.compare/' . $project);
            }

            if ($result->hasUnexpectedDifferences()) {
                $hasUnexpectedDifferences = true;
            }
        }

        if ($hasUnexpectedDifferences) {
            $output->writeln("<error>Comparison FAILED: the writers produced different output.</error>");
            return 1;
        }

        $output->writeln("<info>Comparison OK: both writers produce identical output.</info>");
        return 0;
    }
}
