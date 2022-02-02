<?php

namespace Ryssbowh\ScssPhp\interfaces;

use Ryssbowh\ScssPhp\Asset;
use Ryssbowh\ScssPhp\Compiler;

interface PluginInterface
{
    /**
     * Initialize plugin
     * 
     * @param  Compiler $compiler
     */
    public function init(Compiler $compiler);

    /**
     * Define new events
     * 
     * @return array
     */
    public function defineEvents(): array;
}