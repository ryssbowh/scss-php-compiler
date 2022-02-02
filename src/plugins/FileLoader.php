<?php

namespace Ryssbowh\ScssPhp\plugins;

use Ryssbowh\ScssPhp\Asset;
use Ryssbowh\ScssPhp\Compiler;
use Ryssbowh\ScssPhp\Plugin;
use Ryssbowh\ScssPhp\exceptions\FileLoaderException;

class FileLoader extends Plugin
{
    /**
     * @var string Regular expression to match the file, the full path including query and fragment must be matched.
     * example: '/.+.(?:ico|jpg|jpeg|png|gif)([\?#].*)?$/'
     */
    public $test;

    /**
     * @var int Limit until where images will be encoded in base64 instead of exporting them, defaults to 8kb. 0 to disable
     */
    public $limit = 8192;

    /**
     * @var string Force the Mimetype for the encoded files
     */
    public $mimetype;

    /**
     * @var string Name of the extracted files
     */
    public $name = '[path][name].[ext]';

    /**
     * @var array Encoded files cache
     */
    protected $encoded;

    /**
     * @inheritDoc
     */
    public function init(Compiler $compiler)
    {
        if (!$this->test) {
            throw new FileLoaderException("Test argument must be specified");
        }
         if (!$this->name) {
            throw new FileLoaderException("Name argument must be specified");
        }
        parent::init($compiler);
        $this->compiler->on('beforeCompile', [$this, 'beforeCompile']);
        $this->compiler->on('extractAsset', [$this, 'extractAsset']);
    }

    /**
     * Reset internal cache
     * 
     * @param  array  $files
     */
    public function beforeCompile(array $files)
    {
        $this->encoded = [];
    }

    /**
     * Extract an asset
     * 
     * @param  string $path
     * @return ?string
     */
    public function extractAsset(string $path): ?string
    {
        if (!preg_match($this->test, $path)) {
            return null;
        }
        preg_match('/([^#\?]+)([#\?].+)?/', $path, $matches);
        $path = $matches[1];
        $suffix = $matches[2] ?? '';
        if (isset($this->encoded[$path])) {
            return $this->encoded[$path];
        }
        if (!file_exists($path)) {
            return null;
        }
        $relativeSrcFolder = dirname(str_replace($this->compiler->getSrcFolder() . DIRECTORY_SEPARATOR, '', $path)) . DIRECTORY_SEPARATOR;
        return $this->_extractAsset($path, $suffix, $relativeSrcFolder);
    }

    /**
     * Extract an asset, path is a known file here
     * 
     * @param  string $path
     * @param  string $suffix
     * @return string
     */
    protected function _extractAsset(string $path, string $suffix, string $relativeSrcFolder): string
    {
        if ($data = $this->encodeBase64($path)) {
            $this->encoded[$path] = $data;
            return $data;
        }
        $name = str_replace(['[path]', '[name]', '[ext]', '[hash]'], [
            $relativeSrcFolder,
            pathinfo($path, PATHINFO_FILENAME),
            pathinfo($path, PATHINFO_EXTENSION),
            $this->compiler->getHash(file_get_contents($path))
        ], $this->name);
        $asset = new Asset([
            'publicPath' => $this->compiler->getRelativeDestFolder(),
            'srcFile' => $path,
            'fileName' => $name,
            'name' => $name . $suffix
        ]);
        $this->compiler->addAsset($asset);
        return $name . $suffix;
    }

    /**
     * Encode an image in base64 if the file is small enough
     * 
     * @param  string $path
     * @return ?string
     */
    protected function encodeBase64(string $path): ?string
    {
        if (filesize($path) > $this->limit) {
            return null;
        }
        $mime = mime_content_type($path);
        $mimeOutput = $this->mimetype ?? $mime;
        if ($mime == 'image/svg+xml') {
            return 'data:' . $mimeOutput . file_get_contents($path);
        }
        return 'data:' . $mimeOutput . ';base64,' . base64_encode(file_get_contents($path));
    }
}