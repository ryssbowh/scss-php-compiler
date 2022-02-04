<?php

namespace Ryssbowh\ScssPhp;

use MatthiasMullie\Minify;
use Ryssbowh\ScssPhp\CompilationResults;
use Ryssbowh\ScssPhp\exceptions\EventException;
use Ryssbowh\ScssPhp\exceptions\NotFoundException;
use Ryssbowh\ScssPhp\exceptions\WrongParameterException;
use Ryssbowh\ScssPhp\helpers\StringHelper as S;
use Ryssbowh\ScssPhp\interfaces\PluginInterface;
use ScssPhp\ScssPhp\Compiler as ScssCompiler;
use ScssPhp\ScssPhp\Logger\LoggerInterface;
use ScssPhp\ScssPhp\OutputStyle;
use ScssPhp\ScssPhp\Util;
use Stringy\Stringy;
use Symfony\Component\Filesystem\Filesystem;
use axy\sourcemap\SourceMap;

class Compiler extends BaseObject
{
    /**
     * @var string Sourcemaps : 'none', 'inline' or 'file'
     */
    public $sourcemaps = 'file';

    /**
     * @var string Css output style, 'expanded' or 'minified'
     * Minifying the css will make the sitemaps innaccurate
     */
    public $style = 'expanded';

    /**
     * @var boolean Clean destination directory before compiling
     */
    public $cleanDestination = true;

    /**
     * @var string Destination file name format
     */
    public $fileName = '[name].[hash]';

    /**
     * @var string Hash method for hashing content and asset names
     * @see https://www.php.net/manual/en/function.hash.php
     */
    public $hashMethod = 'crc32b';

    /**
     * @var boolean Disables compiler cache
     */
    public $disableCache = false;

    /**
     * @var boolean Force cache refresh
     */
    public $forceCacheRefresh = false;

    /**
     * @var integer Cache lifetime
     */
    public $cacheLifetime = 604800;

    /**
     * @var boolean Cache checks import resolutions
     */
    public $cacheCheckImportResolutions = true;

    /**
     * @var string Cache directory
     */
    public $cacheFolder = __DIR__ . '/cache';

    /**
     * @var string Absolute public path
     */
    public $publicFolder;

    /**
     * @var array
     */
    public $aliases = [];

    /**
     * @var array Import paths to resolve imports
     */
    public $importPaths = [];

    /**
     * @var array as defined by ScssPhp\ScssPhp\SourceMap\SourceMapGenerator::defaultOptions
     */
    public $sourcemapOptions = [
        'sourceMapRootpath' => '/',
    ];

    /**
     * @var ScssCompiler Underlying Scss compiler
     * @see https://github.com/scssphp/scssphp
     */
    protected $compiler;

    /**
     * @var string Relative destination file path
     */
    protected $relativeDestFile;

    /**
     * @var string Relative destination folder
     */
    protected $relativeDestFolder;

    /**
     * @var string Absolute destination file
     */
    protected $destFile;

    /**
     * @var string Current source folder
     */
    protected $srcFolder;

    /**
     * @var string Current source file
     */
    protected $srcFile;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var array
     */
    protected $plugins = [];

    /**
     * @var Assets Asset manager
     */
    protected $assets;

    /**
     * @var Events Events manager
     */
    protected $events;

    protected $definedEvents = [
        'beforeCompile',
        'afterCompile',
        'beforeCompileFile',
        'afterCompileFile',
        'beforeWriteAssets',
        'afterWriteAssets',
        'beforeAddAsset',
        'afterAddAsset',
        'extractAsset',
        'beforeExtractAssets',
        'afterExtractAssets',
        'beforeOptimize',
        'afterOptimize',
        'beforeWriteSourcemaps',
        'afterWriteSourcemaps',
        'importNotFound'
    ];

    protected $sourcemapsMap = [
        'none' => ScssCompiler::SOURCE_MAP_NONE,
        'inline' => ScssCompiler::SOURCE_MAP_INLINE,
        'file' => ScssCompiler::SOURCE_MAP_FILE
    ];

    public function __construct(array $options = [], array $plugins = [], ?LoggerInterface $logger = null)
    {
        $this->fs = new Filesystem();
        $this->assets = new Assets($this);
        $this->events = new Events($this, $this->definedEvents);
        $this->setLogger($logger);
        parent::__construct($options);
        foreach ($plugins as $plugin) {
            $this->addPlugin($plugin);
        }
    }

    /**
     * Set the logger
     * 
     * @param ?LoggerInterface $logger
     */
    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Add a plugin
     * 
     * @param PluginInterface $plugin
     */
    public function addPlugin(PluginInterface $plugin)
    {
        $plugin->init($this);
        $this->plugins[] = $plugin;
        return $this;
    }

    /**
     * Compile some files, files parameter like so
     * [
     *     'relative/to/srcFolder/app.scss' => 'relative/to/publicFolder/app.css'
     * ]
     * 
     * @param  array  $files
     * @param  string $srcFolder
     * @param  string $srcFile override the source file to resolve import. This is used when the scss imports scss or assets that are in a different folder altogether
     * @return Compiler
     */
    public function compile(array $files, string $srcFolder, ?string $srcFile = null): self
    {
        $this->checkOptions();
        foreach ($this->plugins as $plugin) {
            $this->events->define($plugin->defineEvents());
        }
        $this->trigger('beforeCompile', [$files]);
        $this->srcFolder = rtrim($srcFolder, DIRECTORY_SEPARATOR);
        foreach ($files as $src => $dest) {
            if (!$this->fs->isAbsolutePath($src)) {
                $src = $srcFolder . DIRECTORY_SEPARATOR . $src; 
            }
            $this->srcFile = $srcFile ?? $src;
            $this->destFile = $this->publicFolder . DIRECTORY_SEPARATOR . $dest;
            $this->_compile($src, $dest);
        }
        $this->writeAssets();
        $this->trigger('afterCompile', [$files]);
        $this->assets->empty();
        return $this;
    }

    /**
     * Add one or several import paths
     * 
     * @param string|array $path
     * @return self
     */
    public function appendImportPaths($path): self
    {
        $path = is_array($path) ? $path : [$path];
        $this->importPaths = array_unique(array_merge($this->importPaths, $path));
        return $this;
    }

    /**
     * Prepend one or several import paths
     * 
     * @param string|array $path
     * @return self
     */
    public function prependImportPaths($path): self
    {
        $path = is_array($path) ? $path : [$path];
        foreach (array_reverse($path) as $path) {
            array_unshift($this->importPaths, $path);
        }
        $this->importPaths = array_unique($this->importPaths);
        return $this;
    }

    /**
     * Get the source folder (working directory)
     * 
     * @return string
     */
    public function getSrcFolder(): string
    {
        return $this->srcFolder;
    }

    /**
     * Get the destination folder of the source file currently compiling
     * 
     * @return string
     */
    public function getDestFolder(): string
    {
        return dirname($this->destFile);
    }

    /**
     * get the destination file of the source file currently compiling
     * 
     * @return string
     */
    public function getDestFile(): string
    {
        return $this->destFile;
    }

    /**
     * Get the destination file, relative to the public folder
     * 
     * @return string
     */
    public function getRelativeDestFile(): string
    {
        return S::replaceBeginning($this->destFile, $this->getDestFolder() . DIRECTORY_SEPARATOR, '');
    }

    /**
     * Get the destination folder, relative to the public folder
     * 
     * @return string
     */
    public function getRelativeDestFolder(): string
    {
        $folder = S::replaceBeginning($this->getDestFolder(), $this->publicFolder, '');
        return rtrim($folder, DIRECTORY_SEPARATOR);
    }

    /**
     * Get the line number currently compiled
     * 
     * @return int
     */
    public function getLineNumber(): int
    {
        return $this->compiler->getSourcePosition()[1];
    }

    /**
     * Get the current file being compiled
     * 
     * @return string
     */
    public function getCurrentFile(): string
    {
        $currentFile = $this->compiler->getSourcePosition()[0];
        if (!$currentFile) {
            return $this->srcFile;
        }
        return $currentFile;
    }

    /**
     * Get the directory of the file being compiled
     * 
     * @return string
     */
    public function getCurrentFolder(): string
    {
        $file = $this->getCurrentFile();
        if (!$file) {
            return $this->getSrcFolder();
        }
        return dirname($file);
    }

    /**
     * Get the current folder, relative to src folder
     * 
     * @return string
     */
    public function getRelativeCurrentFolder(): string
    {
        return S::replaceBeginning($this->getCurrentFolder(), $this->getSrcFolder() . DIRECTORY_SEPARATOR, '');
    }

    /**
     * Add an alias
     * 
     * @param string $alias
     * @param string $subFolder
     * @return Compiler
     */
    public function addAlias(string $alias, string $subFolder): self
    {
        $this->aliases[$alias] = $subFolder;
        return $this;
    }

    /**
     * Remove an alias
     * 
     * @param  string $alias
     * @return Compiler
     */
    public function removeAlias(string $alias): self
    {
        if (isset($this->aliases[$alias])) {
            unset($this->aliases[$alias]);
        }
        return $this;
    }

    /**
     * Add an asset
     * 
     * @param Asset $asset
     * @return Compiler
     */
    public function addAsset(Asset $asset): self
    {
        $this->trigger('beforeAddAsset', [$asset]);
        $this->assets->add($asset);
        $this->trigger('afterAddAsset', [$asset]);
        return $this;
    }

    /**
     * Get the asset manager
     * 
     * @return Assets
     */
    public function getAssets(): Assets
    {
        return $this->assets;
    }

    /**
     * Get the filesystem helper
     * 
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->fs;
    }

    /**
     * Get the event manager
     * 
     * @return Events
     */
    public function getEvents(): Events
    {
        return $this->events;
    }

    /**
     * Logs a warn message
     * 
     * @param  mixed $message
     * @param  boolean $deprecation
     * @return Compiler
     */
    public function warn($message, $deprecation = false): self
    {
        if ($this->logger) {
            $this->logger->warn($message, $deprecation);
        }
        return $this;
    }

    /**
     * Logs a debug message
     * 
     * @param  mixed $message
     * @return Compiler
     */
    public function debug($message): self
    {
        if ($this->logger) {
            $this->logger->debug($message);
        }
    }

    /**
     * Get a hash for some data
     * 
     * @param  string $data
     * @return string
     */
    public function getHash(string $data): string
    {
        return S::hash($data, $this->hashMethod);
    }

    /**
     * Triggers an event
     * 
     * @param  string  $event
     * @param  array   $params
     * @param  mixed   $returnValue The value that should be returned at the end (if not modified)
     * @param  boolean $stopOnValue Should the event stop when it gets a value (not null) from a callback
     * @return mixed
     */
    public function trigger(string $event, array $params = [], $returnValue = null, bool $stopOnValue = false)
    {
        return $this->events->trigger($event, $params, $returnValue, $stopOnValue);
    }

    /**
     * Register a callable to an event
     * 
     * @param  string         $event
     * @param  array|function $callable
     * @return self
     */
    public function on(string $event, $callable, int $order = 10): Compiler
    {
        $this->events->register($event, $callable, $order);
        return $this;
    }

    /**
     * Write assets on disk
     */
    protected function writeAssets()
    {
        $this->trigger('beforeWriteAssets', [$this->assets]);
        $this->assets->write($this->publicFolder);
        $this->trigger('afterWriteAssets', [$this->assets]);
    }

    /**
     * Compile a file
     * 
     * @param  string $scssFile
     */
    protected function _compile(string $scssFile)
    {
        if (!$this->fs->exists($scssFile)) {
            throw NotFoundException::source($scssFile);
        }
        if ($this->cleanDestination) {
            $this->cleanFolder($this->getDestFolder());
        }
        $compiler = $this->getCompiler();
        $scss = new ScssSource([
            'scss' => file_get_contents($scssFile),
            'file' => $scssFile
        ]);
        $this->trigger('beforeCompileFile', [$scss]);
        $results = $compiler->compileString($scss->scss, $this->srcFile);
        $results = new CompilationResults([
            'css' => $results->getCss(),
            'sourcemaps' => $results->getSourceMap()
        ]);
        $this->extractAssets($results);
        $this->optimize($results);
        $fileName = pathinfo($this->destFile,  PATHINFO_BASENAME);
        $name = str_replace(['[name]', '[hash]'], [
            pathinfo($this->destFile, PATHINFO_FILENAME),
            $this->getHash($results->css)
        ], $this->fileName) . '.css';
        $mapsAsset = $this->writeSourcemaps($results, $name, $fileName);
        $asset = new Asset([
            'publicPath' => $this->getRelativeDestFolder(),
            'name' => $fileName,
            'content' => $results->css,
            'fileName' => $name
        ]);
        $this->addAsset($asset);
        if ($mapsAsset) {
            $this->addAsset($mapsAsset);
        }
        $this->trigger('afterCompileFile', [$scss, $results]);
    }

    /**
     * Parse css and extract assets
     * 
     * @param CompilationResults $results
     */
    protected function extractAssets(CompilationResults $results)
    {
        $this->trigger('beforeExtractAssets', [$results]);
        //Match path and query in one go
        if (preg_match_all('/url\(["\']?([^)"\'#\?]+)([#\?][^"\'\)]+)?["\']?\)/', $results->css, $matches, PREG_OFFSET_CAPTURE)) {
            $map = new SourceMap(json_decode($results->sourcemaps, true));
            $newCss = Stringy::create($results->css);
            $done = [];
            foreach ($matches[1] as $index => $match) {
                $path = $match[0];
                $fullMatch = $matches[0][$index];
                if (in_array($fullMatch[0], $done)) {
                    continue;
                }
                $done[] = $fullMatch[0];
                $suffix = $matches[2][$index][0] ?? '';
                //The full match offset is a byte offset which needs converted into string offset :
                $offsetInCss = $this->utf8_byte_offset_to_unit($results->css, $fullMatch[1]);
                $lineNumber = substr_count(mb_substr($results->css, 0, $offsetInCss), PHP_EOL) + 1;
                $position = $map->getPosition($lineNumber, 0);
                if (!$position) {
                    $this->warn('Could not resolve ' . $fullMatch[0]);
                    continue;
                }
                $srcFile = str_replace($this->getSrcFolder(), '', $position->source->fileName);
                $fullPath = realpath(dirname($position->source->fileName) . DIRECTORY_SEPARATOR . $path);
                if (!$fullPath) {
                    $this->warn($srcFile . ': File not found ' . $path);
                    continue;
                }
                if ($newPath = $this->trigger('extractAsset', [$fullPath . $suffix], null, true)) {
                    $newCss = $newCss->replace($fullMatch[0], 'url(' . $newPath . ')');
                } else {
                    $this->warn($srcFile . ': No plugin configured to handle the asset ' . $path . ', it\'s not been extracted');
                }
            }
            $results->css = $newCss->toString();
        }
        $this->trigger('afterExtractAssets', [$results]);
    }

    /**
     * Transform a byte offset to a position in the string
     * 
     * @param  string $string
     * @param  int $boff
     * @return ?int
     */
    protected function utf8_byte_offset_to_unit($string, $boff): int
    {
        $result = 0;
        for ($i = 0; $i < $boff; ) {
            $result++;
            $byte = $string[$i];
            $base2 = str_pad(
                base_convert((string) ord($byte), 10, 2), 8, "0", STR_PAD_LEFT);
            $p = strpos($base2, "0");
            if ($p == 0) { $i++; }
            elseif ($p <= 4) { $i += $p; }
            else  { return null; }
        }
        return $result;
    }

    /**
     * Get the Scss Compiler
     * 
     * @return ScssCompiler
     */
    protected function getCompiler(): ScssCompiler
    {
        if ($this->compiler !== null) {
            return $this->compiler;
        }
        $cacheOptions = [];
        if (!$this->disableCache) {
            $cacheOptions = [
                'prefix' => 'scssphp_',
                'gcLifetime' => $this->cacheLifetime,
                'forceRefresh' => $this->forceCacheRefresh,
                'cacheDir' => realpath($this->cacheFolder)
            ];
            if ($this->cacheCheckImportResolutions) {
                $cacheOptions['checkImportResolutions'] = true;
            }
            $this->fs->mkdir($this->cacheFolder);
        }
        $compiler = new ScssCompiler($cacheOptions);
        $compiler->setSourceMapOptions($this->sourcemapOptions);
        $compiler->setSourceMap(ScssCompiler::SOURCE_MAP_FILE);
        if ($this->logger) {
            $compiler->setLogger($this->logger);
        }
        $_this = $this;
        $compiler->addImportPath(function ($path) use ($_this) {
            return $_this->import($path);
        });
        $this->compiler = $compiler;
        return $compiler;
    }

    /**
     * Custom import function, resolve aliases and look into all import paths
     * 
     * @param  string $path
     * @return ?string
     */
    protected function import(string $path): ?string
    {
        if (ScssCompiler::isCssImport($path)) {
            return null;
        }
        if (!S::endsWith($path, '.scss')) {
            $path .= '.scss';
        }
        //Try current folder
        $fullPath = realpath($this->getCurrentFolder() . DIRECTORY_SEPARATOR . $path);
        if ($fullPath) {
            return $fullPath;
        }
        //Try import paths
        foreach ($this->importPaths as $importPath) {
            $fullPath = realpath($importPath . DIRECTORY_SEPARATOR . $this->getRelativeCurrentFolder() . DIRECTORY_SEPARATOR . $path);
            if ($fullPath) {
                return $fullPath;
            }
        }
        //Try aliases
        foreach ($this->aliases as $prefix => $subFolder) {
            if (S::startsWith($path, $prefix)) {
                $relativePath = S::replaceBeginning($path, $prefix, '');
                //Try source folder first
                $fullPath = realpath($this->getSrcFolder() . DIRECTORY_SEPARATOR . $subFolder . DIRECTORY_SEPARATOR . $relativePath);
                if ($fullPath) {
                    return $fullPath;
                }
                //then all import paths
                foreach ($this->importPaths as $importPath) {
                    $fullPath = realpath($importPath . DIRECTORY_SEPARATOR . $subFolder . DIRECTORY_SEPARATOR . $relativePath);
                    if ($fullPath) {
                        return $fullPath;
                    }
                }
            }
        }
        if ($path = $this->trigger('importNotFound', [$path], null, true)) {
            return $path;
        }
        return null;
    }

    protected function optimize(CompilationResults $results)
    {
        $this->trigger('beforeOptimize', [$results]);
        if ($this->style == 'minified') {
            $minifier = new Minify\CSS($results->css);
            $minifier->setImportExtensions([]);
            $results->css = $minifier->minify();
        }
        $this->trigger('afterOptimize', [$results]);
    }

    protected function writeSourcemaps(CompilationResults $results, string $name, string $fileName): ?Asset
    {
        $asset = null;
        $this->trigger('beforeWriteSourcemaps', [$results, $name, $fileName]);
        if ($this->sourcemaps == 'file') {
            $asset = new Asset([
                'publicPath' => $this->getRelativeDestFolder(),
                'name' => $fileName . '.map',
                'content' => $results->sourcemaps,
                'fileName' => $name . '.map',
            ]);
            $results->css .= "/*# sourceMappingURL=$name.map */";
        } elseif ($this->sourcemaps == 'inline') {
            $results->css .= "/*# sourceMappingURL=" .sprintf('data:application/json,%s', Util::encodeURIComponent($results->sourcemaps)) . ' */';
        }
        $this->trigger('afterWriteSourcemaps', [$results, $name, $fileName]);
        return $asset;
    }

    /**
     * Checks the validity of compiler options
     *
     * @throws WrongParameterException
     */
    protected function checkOptions()
    {
        if (!$this->publicFolder) {
            throw WrongParameterException::publicFolder();
        }
        if (!in_array($this->style, ['expanded', 'minified'])) {
            throw WrongParameterException::style($this->style, ['expanded', 'minified']);
        }
        if (!in_array($this->sourcemaps, ['none', 'inline', 'file'])) {
            throw WrongParameterException::sourcemaps(['none', 'inline', 'file']);
        }
        if (!$this->disableCache and $this->cacheFolder === null) {
            throw WrongParameterException::cacheFolder();
        }
        if (!in_array($this->hashMethod, hash_algos())) {
            throw WrongParameterException::hashMethod($this->hashMethod);   
        }
    }

    /**
     * Sanitize a path, remove beginning and ending quotes and double quotes
     * 
     * @param  string $path
     * @return string
     */
    protected function sanitizePath(string $path): string
    {
        if (preg_match('/^"(.*)"$/', $path, $matches)) {
            $path = $matches[1] ?? $path;
        }
        if (preg_match('/^\'(.*)\'$/', $path, $matches)) {
            $path = $matches[1] ?? $path;
        }
        return $path;
    }

    /**
     * Clean a folder
     * 
     * @param  string $folder
     */
    protected function cleanFolder(string $folder)
    {
        if (is_dir($folder)) {
            $files = glob($folder . '*', GLOB_MARK);
            foreach ($files as $file) {
                $this->cleanFolder($file);
            }
        }
        $this->fs->remove($folder);
    }
}