<?php

namespace GisClient\Author\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ChangeDbOwnerCommand extends Command
{
    protected function configure()
    {
        $this->setName("gisclient:changeowner")
            ->setDescription("Change owner of gisclient tables/views to current db user (same name as db-name)")
            //->setHelp("")
            ->addOption(
                "simulate",
                "t",
                InputOption::VALUE_NONE,
                "If set, the db-transaction will not be commited."
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->getDatabase();
        $owner = DB_NAME;
        $schema = DB_SCHEMA;
        $output->writeln("<info>Changing owner of schema $schema and its objects to $owner</info>");
        $db->beginTransaction();
        $yamlString = file_get_contents($this->getRootDir() . "src/Author/Resources/config/dbobjects.yml");
        $dbObjects = Yaml::parse($yamlString)["dbobjects"];

        $sql = "ALTER SCHEMA $schema OWNER TO $owner;";
        if ($output->isVerbose()) {
            $output->writeln("<info>Schema $schema</info>");
        }
        $output->writeln("<info>$sql</info>", OutputInterface::VERBOSITY_VERY_VERBOSE);
        $db->exec($sql);

        foreach ($dbObjects as $object) {
            $sql = "ALTER TABLE $schema.$object OWNER TO $owner;";
            if ($output->isVerbose()) {
                $output->writeln("<info>Object: $object</info>");
            }
            $output->writeln("<info>$sql</info>", OutputInterface::VERBOSITY_VERY_VERBOSE);
            $db->exec($sql);
        }

        if (!$input->getOption('simulate')) {
            $output->writeln("<info>Commit</info>", OutputInterface::VERBOSITY_VERBOSE);
            $db->commit();
        } else {
            $output->writeln("<info>Only simulation, no commit; rollback...</info>");
            $db->rollback();
        }
        $output->writeln("<info>Done.</info>");
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
