<?php

namespace Ryssbowh\ScssPhp;

use Ryssbowh\ScssPhp\helpers\Configure;

class BaseObject
{
    /**
     * Constructor, configure this object's options
     * 
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        Configure::configure($this, $options);
    }
}