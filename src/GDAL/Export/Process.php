<?php

namespace GisClient\GDAL\Export;

class Process
{
    private $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver->getName();
    }

    private function getComand(Task $task)
    {
        //using gdal 1.X
        $cmdTpl = "ogr2ogr -f %s %s %s -overwrite -progress > %s 2> %s & echo $!";
        $cmd = sprintf(
            $cmdTpl,
            $this->driver,
            $task->getFileName(),
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
            $task->getFileName()
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
        $pid = shell_exec($this->getComand($task));

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
        $pid = $this->getPID($task);
        if ($pid) {
            shell_exec(sprintf('kill %d', $pid));
        }
    }
}
