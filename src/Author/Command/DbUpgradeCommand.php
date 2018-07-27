<?php

namespace GisClient\Author\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DbUpgradeCommand extends Command
{
    protected function configure()
    {
        $this->setName("gisclient:dbupgrade")
            ->setDescription("Updates author to current version")
            ->setHelp("Updates author db by executing the script 'doc/update_db_from_3.4.0.sql'")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->getDatabase();
        $output->writeln("<info>Launching Author update script...</info>");
        $db->beginTransaction();

        $upgradeFile = "doc/update_db_from_3.4.0.sql";
        $sql = file_get_contents($this->getRootDir() . $upgradeFile);
        $db->exec($sql);

        $version = $db->query("
            select max(version_name)
            from version
            where version_key = 'author'
        ")->fetchColumn();

        $db->commit();
        $output->writeln("<info>Done. Author version: $version</info>");
    }

    /**
     * @return \PDO
     */
    private function getDatabase()
    {
        return \GCApp::getDB();
    }

    private function getRootDir()
    {
        return __DIR__ . "/../../../";
    }
}
