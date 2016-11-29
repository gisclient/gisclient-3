<?php

namespace GisClient\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class CommandLineLogger extends AbstractLogger implements LoggerInterface
{

    public function log($level, $message, array $context = array())
    {
        printf("%-9s %s\n", "{$level}:", $message);
    }
}