<?php

namespace Ryssbowh\ScssPhp\helpers;

use Stringy\Stringy as S;

class StringHelper
{
    public static function __callStatic($name, $args)
    {
        return S::create($args[0])->$name(...array_slice($args, 1));
    }
}