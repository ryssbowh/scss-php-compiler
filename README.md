Based on [scssphp/scssphp](https://github.com/scssphp/scssphp) this package allows more control on the compilation process through plugins, a bit similar to how webpack does it. That allows for example to define aliases, custom imports, asset extraction, manifest, name hashing and all sorts of plugins.

The compiler allows compiling several files at the same time, all outputed in the same public folder. During the compilation it will trigger events that any plugin can subscribe to, here's the general workflow :

- plugins are initialised
- compilation starts
- a file compilation starts
- asset extraction starts
- asset extraction ends
- css optimisation starts
- css optimisation ends
- sourcemap writing starts
- sourcemap writing ends
- a file compilation ends
- assets are written on disk
- compilation ends

## Usage

Here's how to define a new compiler with default options and run it :

```
$compiler = new Compiler([
    'publicFolder' => '/absolute/path/to/public/folder'
]);
$compiler->compile([
    'relative/or/absolute/path/to/srcFile1.scss' => 'relative/path/to/cssFile1.css',
    'relative/or/absolute/path/to/srcFile2.scss' => 'relative/path/to/cssFile2.css',
], '/absolute/path/to/src/folder', null);
```
First argument of `compile` is an array index by the relative path (to the source folder) of the scss files, the values are relative paths (to the public folder) or absolute paths of css files. If you give relative paths, the absolute path will be built using the second argument.

Second argument is the source folder, where all imports will be considered from.

Third argument is to "fake" the source file (see below)

Default options for the compiler :

```
[
    'sourcemaps' => 'file',
    'style' => 'expanded',
    'cleanDestination' => true,
    'fileName' => '[name].[hash]',
    'hashMethod' => 'crc32b',
    'disableCache' => false,
    'forceCacheRefresh' => false,
    'cacheLifetime' => 604800,
    'cacheCheckImportResolutions' => true,
    'cacheFolder' => __DIR__ . '/cache',
    'importPaths' => [],
    'aliases' => []
]
```

This example won't extract assets so unless your assets paths match in the scss, chances are they will not resolve. It won't write a manifest either. For that you need plugins.

### Faking the source folder

In some (probably rare) cases you may want to compile a scss file situated in a folder as if it was in another folder, in this example we want the compiler to consider that `folder1/src.scss` as if it was in the `folder2/templates` folder :

```
- folder1
    - src.scss
- folder2
    - assets
        - img.jpg
    - templates
        - file.twig
```
```
//src.scss
a {
    background('../assets/img.jpg')
}
```

If you were using the example above to compile, the assets wouldn't be found, as they are in a different folder. Compiling with this :

```
$compiler = new Compiler([
    'publicFolder' => '/public'
]);
$compiler->compile([
    '/folder1/src.scss' => 'dest.css', //Note the (required) absolute path here
], '/folder2', '/folder2/templates/file.twig');
```
will resolve your imports and asset urls nicely, giving this results (assuming you have a plugin that extracts the .jpg files) :
```
- public
    - assets
        - img.jpg
    - dest.css
```
```
//dest.css
a {
    background('assets/img.jpg')
}
```

## Aliases

Aliases can be set to resolve imports :

```
$compiler = new Compiler([
    'publicFolder' => '/absolute/path/to/public/folder',
    'aliases' => [
        '~' => 'node_modules'
    ]
]);
//Or
$compiler
    ->addAlias('#', 'node_modules')
    ->removeAlias('~');
```

The aliases can be subfolders (like `assets/node_modules`) and are relative to the source folder. After this example you'll be able to write in your scss :

```
import "~folder/file.scss";
```

## Import paths

You can define custom import paths, the compiler will look in those folders if it can't find an import in the current folder :

```
$compiler = new Compiler([
    'publicFolder' => '/absolute/path/to/public/folder',
    'importPaths' => [
        'my/folder',
        'my/other/folder'
    ]
]);
//Or
$compiler
    ->appendImportPaths('folder')
    ->prependImportPaths('folder2');
```

The compiler will resolve imports according to the folder structure of the current file and look into import paths by ascending order. An example that would work :

```
- folder1
    - app.scss
- folder 2
    - subfolder1
        - imported.scss
```

```
$compiler = new Compiler([
    'publicFolder' => '/absolute/path/to/public/folder',
    'importPaths' => [
        'folder2'
    ]
]);
$compiler->compile([
    'app.scss' => 'cssFile1.css',
], 'folder1');
```
app.scss :
```
@import "subfolder1/imported.scss";
```
:warning: Import paths apply to the `@custom` rule only, not to assets defined within `url()` functions.

## Logging

The compiler constructor accepts a 3rd argument to define your own logger :

```
use ScssPhp\ScssPhp\Logger\LoggerInterface;

class Logger implements LoggerInterface
{
    public function warn($message, $deprecation = false)
    {
        //log a warning here
    }

    public function debug($message)
    {
        //log a debug message here
    }
}

$compiler = new Compiler([
    'publicFolder' => '/absolute/path/to/public/folder'
], [], new Logger);
```

## Plugins

A plugin can hook in the compilation process and change its output by registering to events.

The events and callbacks defined by this package are as follow :

```
'beforeCompile': function (array $files) 
'afterCompile': function (array $files) 
'beforeCompileFile': function (ScssSource $source)
'afterCompileFile': function (ScssSource $source, CompilationResults $results)
'beforeWriteAssets': function (Assets $assets)
'afterWriteAssets': function (Assets $assets) 
'beforeAddAsset': function (Asset $asset)
'afterAddAsset': function (Asset $asset) 
'beforeExtractAssets': function (CompilationResults $results)
'afterExtractAssets': function (CompilationResults $results)
'extractAsset': function (string $path) : ?string  //returning a string will prevent other plugins to access this event
'beforeOptimize': function (CompilationResults $results)
'afterOptimize': function (CompilationResults $results)
'beforeWriteSourcemaps': function (CompilationResults $results, string $name, string $fileName)
'afterWriteSourcemaps': function (CompilationResults $results, string $name, string $fileName)
'importNotFound': function (string $import): ?string  //returning a string will prevent other plugins to access this event
```

### Json manifest

```
use Ryssbowh\ScssPhp\plugins\JsonManifest;

$plugins = [
    new JsonManifest([
        'name' => 'manifest' //default
    ])
];
$compiler = new Compiler([
    'publicFolder' => '/path/to/public/folder'
], $plugins);
```

This plugin will write a json manifest file in the public folder at the end of compilation.  
It defines 2 new events :

```
'beforeWriteManifest': function (string $file, array $manifest): ?array //Return an array to modify the manifest
'afterWriteManifest': function (array $file, array $manifest)
```

### File loader

```
use Ryssbowh\ScssPhp\plugins\FileLoader;

$plugins = [
    new FileLoader([
        'test' => '/.+.(?:ico|jpg|jpeg|png|gif)([\?#].*)?$/',
        'limit' => 8192, //default
        'mimetype' => null //Auto detection
    ])
];
$compiler = new Compiler([
    'publicFolder' => '/path/to/public/folder'
], $plugins);
```

This plugin extract assets in the public folder, or encode them in base64.

The `limit` argument defines the limit of file sizes under which files will be encoded in base64, which would minimise your http connections to fetch assets. Keep in mind that encoding in base64 will raise the size of the final css file significantly.

This example would give you a good start to extract files and fonts :

```
$plugins = [
    new FileLoader([
        'test' => '/.+.(?:ico|jpg|jpeg|png|gif)([\?#].*)?$/',
    ]),
    new FileLoader([
        'test' => '/.+.svg([\?#].*)?$/',
    ]),
    new FileLoader([
        'test' => '/.+.ttf([\?#].*)?$/',
        'mimetype' => 'application/octet-stream',
    ]),
    new FileLoader([
        'test' => '/.+.woff([\?#].*)?$/',
        'mimetype' => 'application/font-woff',
    ]),
    new FileLoader([
        'test' => '/.+.woff2([\?#].*)?$/',
        'mimetype' => 'application/font-woff',
    ]),
    new FileLoader([
        'test' => '/.+.eot([\?#].*)?$/',
    ]),
];
```

### Make your own plugins

A plugin only need to define a `init` function where it can register to some compilation events :

```
use Ryssbowh\ScssPhp\Compiler;
use Ryssbowh\ScssPhp\Plugin;

class MyPlugin extends Plugin
{
    public $argument;

    public function init(Compiler $compiler)
    {
        //Validate arguments here
        //Call parent init function
        parent::init($compiler);
        //Subscribe to events
        $this->compiler->on('beforeCompile', [$this, 'beforeCompile']);
    }

    //Optionally define new events this plugin will trigger
    public function defineEvents(): array
    {
        return ['beforeNewPlugin'];
    }

    //This will be called before the compilation starts
    public function beforeCompile(array $files)
    {
        //Optionally trigger a custom event
        $this->compiler->trigger('beforeNewPlugin');
    }
}
```

`Compiler::on(string $event, $callable, int $order = 10)` third argument `$order` is an int (default 10) defining the order the callback should be called in.

## Roadmap

- chunking
- async events