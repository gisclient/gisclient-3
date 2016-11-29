<?php

namespace GisClient\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * A logger to don't log
 */
class EmptyLogger extends AbstractLogger implements LoggerInterface
{

    public function log($level, $message, array $context = array())
    {
        
    }
}