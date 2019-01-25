<?php

namespace GisClient\GDAL\Export;

class Process
{
    private $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver->getName();
    }

    private function check(Task $task)
    {
        $logDir = dirname($task->getLogFile());
        if (!is_writable($logDir)) {
            throw new \RuntimeException("Error: Directory not exists or not writable '{$logDir}'", 1);
        }
    }

    private function getCommand(Task $task)
    {
        //using gdal 1.X
        $cmdTpl = "ogr2ogr -f %s %s %s -overwrite -progress > %s 2> %s & echo $!";
        $cmd = sprintf(
            $cmdTpl,
            $this->driver,
            $task->getFilePath(),
            $task->getSource(),
            $task->getLogFile(),
            $task->getErrFile()
        );
        
        return $cmd;
    }

    private function getPID(Task $task)
    {
        $result = shell_exec(sprintf(
            'ps x | grep "%s" | grep "%s"',
            'ogr2ogr -f ' . $this->driver,
            $task->getFilePath()
        ));
        $r = preg_split("/\n/", $result);
        for ($i = 0; $i < count($r); $i++) {
            $p = preg_split("/\s+/", trim($r[$i]));
            if (in_array($this->driver, $p) && !in_array('grep', $p)) {
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
