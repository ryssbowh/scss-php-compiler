<?php

namespace Ryssbowh\ScssPhp\plugins;

use Ryssbowh\ScssPhp\Assets;
use Ryssbowh\ScssPhp\Compiler;
use Ryssbowh\ScssPhp\Plugin;
use Ryssbowh\ScssPhp\exceptions\JsonManifestException;
use Ryssbowh\ScssPhp\helpers\StringHelper as S;

class JsonManifest extends Plugin
{
    /**
     * @var string
     */
    public $name = 'manifest';

    public function init(Compiler $compiler)
    {
        if (!$this->name) {
            throw new JsonManifestException("Name argument must be specified");
        }
        parent::init($compiler);
        $this->compiler->on('afterWriteAssets', [$this, 'writeManifest']);
    }

    /**
     * @inheritDoc
     */
    public function defineEvents(): array
    {
        return ['beforeWriteManifest', 'afterWriteManifest'];
    }

    public function writeManifest(Assets $assets)
    {
        $file = $this->compiler->getDestFolder() . DIRECTORY_SEPARATOR . $this->name;
        if (!S::endsWith($file, '.json')) {
            $file .= '.json';
        }
        $manifest = [];
        foreach ($assets->get() as $asset) {
            $manifest[$asset->name] = $asset->fileName;
        }
        $manifest = $this->compiler->trigger('beforeWriteManifest', [$file, $manifest], $manifest);
        file_put_contents($file, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->compiler->trigger('afterWriteManifest', [$file, $manifest]);
    }
}