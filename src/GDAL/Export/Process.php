<?php

namespace GisClient\GDAL\Export;

class Process
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

    private function getCommand(Task $task)
    {
        //using gdal 1.X
        $cmdTpl = "ogr2ogr -f %s %s %s %s -overwrite -progress > %s 2> %s & echo $!";
        $cmd = sprintf(
            $cmdTpl,
            $this->driver->getName(),
            $task->getFilePath(),
            $task->getSource(),
            $this->driver->getCmdArguments(),
            $task->getLogFile(),
            $task->getErrFile()
        );
        
        return $cmd;
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
