<?php

namespace Ryssbowh\ScssPhp\exceptions;

class WrongParameterException extends \Exception
{
    public static function sourcemaps(array $valid)
    {
        return new static("Sourcemaps option is invalid. Should be one of " . implode(', ', $valid));
    }

    public static function cacheFolder()
    {
        return new static("The cache is enabled but no cache folder has been set.");
    }

    public static function hashMethod($method)
    {
        return new static("The hash method $method is unvalid, should be one of :" . implode(', ', hash_algos()));
    }

    public static function publicFolder()
    {
        return new static("The public folder is not defined");
    }

    public static function style($style, array $valid)
    {
        return new static("The style $style is not valid. Should be one of : " . implode(', ', $valid));
    }
}