<?php

namespace GisClient\Author\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RefreshMapfileCommand extends Command
{
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $input->getArgument("project");
        $mapset = $input->getArgument("mapset");
        $public = !$input->getOption("temporary");
        $layerMapfile = $input->getOption("layer-mapfile");

        if ($project === "all") {
            // all mapsets of all projects
            foreach (\GCAuthor::getProjects() as $project) {
                $this->refreshAllMapsetsOfProject(
                    $output,
                    $project["project_name"],
                    $public,
                    $layerMapfile
                );
            }
        } elseif ($mapset === "all") {
            // all mapsets of specified project
            $this->refreshAllMapsetsOfProject(
                $output,
                $project,
                $public,
                $layerMapfile
            );
        } else {
            $this->refreshMapset(
                $output,
                $project,
                $mapset,
                $public,
                $layerMapfile
            );
        }

        $errors = \GCError::get();
        if (!empty($errors)) {
            throw new \Exception("GCErrors: " . implode("\n", $errors));
        }
        if ($output->isVerbose()) {
            $output->writeln("<info>Done.</info>");
        }
    }

    protected function refreshAllMapsetsOfProject(OutputInterface $output, $project, $public, $layerMapfile)
    {
        if ($output->isVerbose()) {
            $output->writeln("<info>Refreshing all mapsets for project '$project'...</info>");
        }
        \GCAuthor::refreshMapfiles(
            $project,
            $public,
            $layerMapfile
        );
    }

    protected function refreshMapset(OutputInterface $output, $project, $mapset, $public, $layerMapfile)
    {
        if ($output->isVerbose()) {
            $output->writeln("<info>Refreshing mapset '$mapset' for project '$project'...</info>");
        }
        \GCAuthor::refreshMapfile(
            $project,
            $mapset,
            $public,
            $layerMapfile
        );
    }
}
