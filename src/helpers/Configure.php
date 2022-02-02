<?php

namespace Ryssbowh\ScssPhp\helpers;

class Configure
{
    /**
     * Confgure an object
     * 
     * @param  object $object
     * @param  array  $options
     */
    public static function configure(object $object, array $options)
    {
        foreach ($options as $name => $value) {
            $object->$name = $value;
        }
    }
}