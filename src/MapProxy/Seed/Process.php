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

    public function startTask(Task $task)
    {
        $cmdTpl = "%s -f %s -c 1 %s --seed %s > %s 2> %s &";
        $cmd = (sprintf(
            $cmdTpl,
            $this->bin,
            $this->mapConfig,
            $this->seedConfig,
            $task->getTaskName(),
            $task->getLogFile(),
            $task->getErrFile()
        ));

        echo $cmd;
        exec($cmd);

        return $task;
    }
}
