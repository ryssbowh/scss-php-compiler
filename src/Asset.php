<?php

namespace Ryssbowh\ScssPhp;

use Symfony\Component\Filesystem\Filesystem;

class Asset extends BaseObject
{
    /**
     * @var string
     */
    public $content;

    /**
     * @var string
     */
    public $srcFile;

    /**
     * @var string
     */
    public $publicPath;

    /**
     * @var string The file name on disk eg [name].[hash].css
     */
    public $fileName;

    /**
     * @var string The name of the asset eg app.css
     */
    public $name;

    /**
     * Get asset content, either from the content opiton, or from the source file
     * 
     * @return ?string
     */
    public function getContent(): ?string
    {
        if ($this->content !== null) {
            return $this->content;
        }
        if ($this->srcFile !== null and file_exists($this->srcFile)) {
            return file_get_contents($this->srcFile);
        }
        return null;
    }
}