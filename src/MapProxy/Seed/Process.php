<?php

namespace GisClient\MapProxy\Seed;

class Process
{
    private $bin;
    private $mapConfig;
    private $seedConfig;

    public function __construct($path, $mapConfig, $seedConfig)
    {
        if (file_exists($path . 'mapproxy-seed')) {
            $this->bin = $path . 'mapproxy-seed';
        } else {
            throw new \Exception("Error: File not exists '{$path}mapproxy-seed'", 1);
        }
        if (file_exists($mapConfig)) {
            $this->mapConfig = $mapConfig;
        } else {
            throw new \Exception("Error: File not exists '{$mapConfig}'", 1);
        }
        if (file_exists($seedConfig)) {
            $this->seedConfig = $seedConfig;
        } else {
            throw new \Exception("Error: File not exists '{$seedConfig}'", 1);
        }
    }

    private function getCommand(Task $task)
    {
        $cmdTpl = "%s -f %s -c 1 %s --seed %s > %s 2> %s & echo $!";
        $cmd = (sprintf(
            $cmdTpl,
            $this->bin,
            $this->mapConfig,
            $this->seedConfig,
            $task->getTaskName(),
            $task->getLogFile(),
            $task->getErrFile()
        ));
        
        return $cmd;
    }

    private function getPID(Task $task)
    {
        $result = shell_exec(sprintf('ps x | grep "%s" | grep "%s"', $this->bin, $task->getTaskName()));
        $r = preg_split("/\n/", $result);

        for ($i=0; $i < count($r); $i++) {
            $a = preg_split("/\s+/", trim($r[$i]));
            if (in_array($this->bin, $a)) {
                if ($a[4] !== 'sh') {
                    return (int)$a[0];
                }
            }
        }

        return false;
    }

    public function startTask(Task $task)
    {
        $pid = shell_exec($this->getCommand($task));

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

    public function stopTask(Task $task)
    {
        $pid = $this->getPID($task);
        if ($pid) {
            shell_exec(sprintf('kill %d', $pid));
        }
    }
}