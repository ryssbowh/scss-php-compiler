<?php

namespace Ryssbowh\ScssPhp\exceptions;

class NotFoundException extends \Exception
{
    public static function source(string $path)
    {
        return new static("The source file $path doesn't exist");
    }
}