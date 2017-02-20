<?php

namespace GisClient\GDAL\Export;

class Process
{
    private $driver;

    public function __construct($driver)
    {
        $this->driver = $driver;
    }

    private function getComand(Task $task)
    {
        //using gdal 1.X
        $cmdTpl = "ogr2ogr -f %s %s %s -overwrite > %s 2> %s & echo $!";
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

    public function start(Task $task)
    {
        echo $this->getComand($task);
        $pid = shell_exec($this->getComand($task));

        return $pid;
    }
}
