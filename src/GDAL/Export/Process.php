<?php

namespace GisClient\GDAL\Export;

use GisClient\Author\Offline\OfflineProcessInterface;
use GisClient\Author\Offline\OfflineTaskInterface;
use Symfony\Component\Process\Process as SymfonyProcess;

class Process implements OfflineProcessInterface
{
    /**
     * Driver
     *
     * @var Driver
     */
    private $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    private function check(Task $task)
    {
        // check if driver is available
        if (!$this->driver->isAvailable()) {
            throw new \RuntimeException(sprintf(
                "Error: The driver '%s' is not available'",
                $this->driver->getName()
            ));
        }

        // check log directory
        $logDir = dirname($task->getLogFile());
        if (!is_writable($logDir)) {
            throw new \RuntimeException("Error: Directory not exists or not writable '{$logDir}'", 1);
        }
    }

    public function getCommand(OfflineTaskInterface $task, $runInBackground = true, $asArray = false)
    {
        if (!($task instanceof Task)) {
            throw new \Exception('The given task does not match the required class: '.Task::class);
        }

        $commandLine = array_merge(
            [
                "ogr2ogr",
                "-f",
                $this->driver->getName(),
                $task->getFilePath(),
            ],
            $task->getSource(),
            $this->driver->getCmdArguments(),
            [
                '-overwrite',
                '-progress'
            ]
        );
        if ($runInBackground) {
            $commandLine[] = ">";
            $commandLine[] = $task->getLogFile();
            $commandLine[] = "2>";
            $commandLine[] = $task->getErrFile();
            $commandLine[] = "&";
            $commandLine[] = "echo";
            $commandLine[] = "$!";
        }

        if ($asArray) {
            return $commandLine;
        }

        // use Process class from symfony, to escape arguments
        $process = new SymfonyProcess($commandLine);
        return $process->getCommandLine();
    }

    private function getPID(Task $task)
    {
        $result = shell_exec(sprintf(
            'ps x | grep "%s" | grep "%s"',
            'ogr2ogr -f ' . $this->driver->getName(),
            $task->getFilePath()
        ));
        $r = preg_split("/\n/", $result);
        for ($i = 0; $i < count($r); $i++) {
            $p = preg_split("/\s+/", trim($r[$i]));
            if (in_array($this->driver->getName(), $p) && !in_array('grep', $p)) {
                return (int)$p[0];
            }
        }

        return false;
    }

    public function start(Task $task)
    {
        $this->check($task);
        if (!$this->isRunning($task)) {
            $cmd = $this->getCommand($task);
            $pid = shell_exec($this->getCommand($task));
        } else {
            $pid = $this->getPID($task);
        }

        return $pid;
    }

    public function isRunning(Task $task)
    {
        $pid = $this->getPID($task);
        if ($pid !== false) {
            return true;
        }
        return false;
    }

    public function stop(Task $task)
    {
        while ($this->isRunning($task)) {
            $pid = $this->getPID($task);
            if ($pid) {
                shell_exec(sprintf('kill %d', $pid));
            }
        }
    }
}
