<?php

namespace Ryssbowh\ScssPhp;

use Ryssbowh\ScssPhp\interfaces\PluginInterface;

class Plugin extends BaseObject implements PluginInterface
{
    protected $compiler;

    /**
     * @inheritDoc
     */
    public function init(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * @inheritDoc
     */
    public function defineEvents(): array
    {
        return [];
    }
}