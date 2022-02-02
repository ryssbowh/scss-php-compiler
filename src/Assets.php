<?php

namespace Ryssbowh\ScssPhp;

use Ryssbowh\ScssPhp\helpers\Configure;
use Symfony\Component\Filesystem\Filesystem;

class Assets
{
    /**
     * @var Compiler
     */
    protected $compiler;

    /**
     * @var array
     */
    protected $assets = [];

    /**
     * Constructor
     * 
     * @param Compiler $compiler
     */
    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Remove all assets
     * 
     * @return Assets
     */
    public function empty()
    {
        $this->assets = [];
        return $this;
    }

    /**
     * Add an asset
     * 
     * @param  Asset $asset
     * @return Assets
     */
    public function add(Asset $asset)
    {
        if ($this->nameExists($asset->name)) {
            $this->compiler->warn("More than one asset has the name {$asset->name}");
        }
        $this->assets[] = $asset;
        return $this;
    }

    /**
     * Is an asset named $name is defined
     * 
     * @param  string $name
     * @return bool
     */
    public function nameExists(string $name): bool
    {
        foreach ($this->assets as $asset) {
            if ($asset->name === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all assets
     * 
     * @return array
     */
    public function get(): array
    {
        return $this->assets;
    }

    /**
     * Write all assets in a folder
     * 
     * @param string $folder
     */
    public function write(string $folder)
    {
        foreach ($this->assets as $asset) {
            $publicPath = $asset->publicPath ? $asset->publicPath . DIRECTORY_SEPARATOR : '';
            $dest = $folder . DIRECTORY_SEPARATOR . $publicPath . $asset->fileName;
            $this->compiler->getFilesystem()->mkdir(dirname($dest));
            file_put_contents($dest, $asset->getContent());
        }
    }
}