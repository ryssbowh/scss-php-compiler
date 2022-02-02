<?php

namespace Ryssbowh\ScssPhp\Tests;

use PHPUnit\Framework\TestCase;
use Ryssbowh\ScssPhp\Compiler;
use Ryssbowh\ScssPhp\Tests\Helpers\Logger;
use Ryssbowh\ScssPhp\plugins\FileLoader;
use Ryssbowh\ScssPhp\plugins\JsonManifest;

abstract class BaseTest extends TestCase
{
    protected function createCompiler(array $options = [], ?array $plugins = null)
    {
        if (!isset($options['publicFolder'])) {
            $options['publicFolder'] = $this->getPublicFolder();
        }
        if (!isset($options['sourcemaps'])) {
            $options['sourcemaps'] = 'file';
        }
        if (!isset($options['fileName'])) {
            $options['fileName'] = '[name]';
        }
        if (is_null($plugins)) {
            $plugins = $this->getDefaultPlugins();
        }
        $options['cacheCheckImportResolutions'] = true;
        $options['cacheFolder'] = realpath(__DIR__ . '/../cache');
        $compiler = new Compiler($options, $plugins, new Logger);
        $compiler->addAlias('~', 'node_modules');
        return $compiler;
    }

    protected function getPublicFolder(?string $sub = null): string
    {
        return realpath(__DIR__ . '/..') . '/public' . ($sub ? ('/' . $sub) : '');
    }

    protected function getExpectedFile(string $sub): string
    {
        return realpath(__DIR__ . '/../expected/' . $sub);
    }

    protected function getAssetFile(string $asset)
    {
        return realpath(__DIR__ . '/../scss/assets/' . $asset);   
    }

    protected function getSrcFolder(?string $sub = null): string
    {
        return realpath(__DIR__ . '/../scss' . ($sub ? ('/' . $sub) : ''));
    }

    protected function getDefaultPlugins(?int $limit = null, bool $manifest = true): array
    {
        $plugins = [
            new FileLoader([
                'test' => '/.+.(?:ico|jpg|jpeg|png|gif)([\?#].*)?$/',
                'limit' => $limit ?? 8192
            ]),
            new FileLoader([
                'test' => '/.+.svg([\?#].*)?$/',
                'limit' => $limit ?? 8192
            ]),
            new FileLoader([
                'test' => '/.+.ttf([\?#].*)?$/',
                'mimetype' => 'application/octet-stream',
                'limit' => $limit ?? 8192
            ]),
            new FileLoader([
                'test' => '/.+.woff([\?#].*)?$/',
                'mimetype' => 'application/font-woff',
                'limit' => $limit ?? 8192
            ]),
            new FileLoader([
                'test' => '/.+.woff2([\?#].*)?$/',
                'mimetype' => 'application/font-woff',
                'limit' => $limit ?? 8192
            ]),
            new FileLoader([
                'test' => '/.+.eot([\?#].*)?$/',
                'limit' => $limit ?? 8192
            ]),
        ];
        if ($manifest) {
            $plugins[] = new JsonManifest;
        }
        return $plugins;
    }
}