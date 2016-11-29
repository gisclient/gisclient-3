<?php

namespace GisClient\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class AuthorLogger extends AbstractLogger implements LoggerInterface
{

    public function log($level, $message, array $context = array())
    {
        print_debug(sprintf("%-9s %s", "{$level}:", $message));
    }
}