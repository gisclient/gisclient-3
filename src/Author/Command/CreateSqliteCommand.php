<?php

namespace GisClient\Author\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use GisClient\Author\Layer;
use GisClient\GDAL\Export\Process as GDALProcess;
use GisClient\GDAL\Export\SQLite\Task as SQLiteTask;
use GisClient\GDAL\Export\SQLite\Driver as SQLiteDriver;

class CreateSqliteCommand extends Command
{
    protected function configure()
    {
        $this->setName("gisclient:offline:create:sqlite")
            ->setDescription("Create sqlite db for offline usage")
            ->addArgument(
                "layer_id",
                InputArgument::REQUIRED,
                "Which layer id?"
            )
            ->addArgument(
                "filename",
                InputArgument::REQUIRED,
                "Output filename?"
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Create sqlite db...</info>");
        
        $gdalProcess = new GDALProcess(new SQLiteDriver());

        $layer = new Layer($input->getArgument('layer_id'));
        $filename = $input->getArgument('filename');
        $task = new SQLiteTask($layer, $filename, DEBUG_DIR);
        $gdalProcess->start($task);
        do {
            sleep(1);
        } while ($gdalProcess->isRunning($task));
    }
}
