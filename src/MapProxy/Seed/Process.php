<?php

namespace GisClient\MapProxy\Seed;

class Process
{
    private $bin;

    public function __construct($path)
    {
        $this->bin = $path . '/mapproxy-seed';
    }

    private function check(Task $task)
    {
        if (!file_exists($this->bin)) {
            throw new \RuntimeException("Error: File not exists '{$this->bin}'", 1);
        }
        $mapConfig = $task->getMapConfig();
        if (!file_exists($mapConfig)) {
            throw new \RuntimeException("Error: File not exists '{$mapConfig}'", 1);
        }
        $seedConfig = $task->getSeedConfig();
        if (!file_exists($seedConfig)) {
            throw new \RuntimeException("Error: File not exists '{$seedConfig}'", 1);
        }

        $logDir = dirname($task->getLogFile());
        if (!is_writable($logDir)) {
            throw new \RuntimeException("Error: Directory not exists or not writable '{$logDir}'", 1);
        }
    }

    private function getCommand(Task $task)
    {
        $cmdTpl = "%s -f %s -c 1 %s --seed %s > %s 2> %s & echo $!";
        $cmd = (sprintf(
            $cmdTpl,
            $this->bin,
            $task->getMapConfig(),
            $task->getSeedConfig(),
            $task->getTaskName(),
            $task->getLogFile(),
            $task->getErrFile()
        ));
        
        return $cmd;
    }

    private function getPID(Task $task)
    {
        $result = shell_exec(sprintf('ps x | grep "%s" | grep "%s$"', $this->bin, $task->getTaskName()));
        $r = preg_split("/\n/", $result);

        for ($i = 0; $i < count($r); $i++) {
            $p = preg_split("/\s+/", trim($r[$i]));
            if (in_array($this->bin, $p) && !in_array('grep', $p)) {
                return (int)$p[0];
            }
        }

        return false;
    }

    public function isRunning(Task $task)
    {
        $pid = $this->getPID($task);
        if ($pid !== false) {
            return true;
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
