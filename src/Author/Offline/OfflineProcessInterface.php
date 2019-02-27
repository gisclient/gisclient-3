<?php

namespace GisClient\Author\Offline;

use Symfony\Component\Process\Process;

interface OfflineProcessInterface
{
    /**
     * Get the process
     *
     * @return Process
     */
    public function getCommand(OfflineTaskInterface $task, $runInBackground = true, $asArray = false);
}
