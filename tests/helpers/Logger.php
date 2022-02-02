<?php

namespace Ryssbowh\ScssPhp\Tests\Helpers;

use ScssPhp\ScssPhp\Logger\LoggerInterface;

class Logger implements LoggerInterface
{
    /**
     * @inheritDoc
     */
    public function warn($message, $deprecation = false)
    {
        fwrite(STDERR, 'WARNING: ' . $message, true);
    }

    /**
     * @inheritDoc
     */
    public function debug($message)
    {
        fwrite(STDERR, 'DEBUG: ' . $message, true);
    }
}