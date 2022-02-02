<?php

namespace Ryssbowh\ScssPhp\Tests;

use Ryssbowh\ScssPhp\Compiler;
use Ryssbowh\ScssPhp\plugins\ImagesLoader;
use Ryssbowh\ScssPhp\plugins\JsonManifest;
use ScssPhp\ScssPhp\OutputStyle;

class BasicTest extends BaseTest
{
    public function testCompile()
    {
        $this->createCompiler()->compile(['basic1/app.scss' => 'basic1/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic1/app.css'), $this->getExpectedFile('basic1/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic1/manifest.json'), $this->getExpectedFile('basic1/manifest.json'));
        $this->assertFileExists($this->getPublicFolder('basic1/app.css.map'));
    }

    public function testImport()
    {
        $this->createCompiler()->compile(['basic2/app.scss' => 'basic2/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic2/app.css'), $this->getExpectedFile('basic2/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic2/manifest.json'), $this->getExpectedFile('basic2/manifest.json'));
        $this->assertFileExists($this->getPublicFolder('basic2/app.css.map'));
    }

    public function testAssetsEncoded()
    {
        $compiler = $this->createCompiler([], $this->getDefaultPlugins(15000));
        $compiler->compile(['basic3/app.scss' => 'basic3/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic3/app.css'), $this->getExpectedFile('basic3/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic3/manifest.json'), $this->getExpectedFile('basic3/manifest.json'));
        $this->assertFileExists($this->getPublicFolder('basic3/app.css.map'));
    }

    public function testAssetsExtracted()
    {
        $compiler = $this->createCompiler([], $this->getDefaultPlugins(0));
        $compiler->compile(['basic3/app.scss' => 'basic4/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic4/app.css'), $this->getExpectedFile('basic4/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic4/manifest.json'), $this->getExpectedFile('basic4/manifest.json'));
        $this->assertFileEquals($this->getPublicFolder('basic4/assets/unit.ico'), $this->getAssetFile('unit.ico'));
        $this->assertFileEquals($this->getPublicFolder('basic4/assets/unit.jpg'), $this->getAssetFile('unit.jpg'));
        $this->assertFileEquals($this->getPublicFolder('basic4/assets/unit.jpeg'), $this->getAssetFile('unit.jpeg'));
        $this->assertFileEquals($this->getPublicFolder('basic4/assets/unit.png'), $this->getAssetFile('unit.png'));
        $this->assertFileExists($this->getPublicFolder('basic4/app.css.map'));
    }

    public function testNoSitemaps()
    {
        $compiler = $this->createCompiler([
            'sourcemaps' => 'none'
        ], $this->getDefaultPlugins(15000));
        $compiler->compile(['basic1/app.scss' => 'basic5/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic5/app.css'), $this->getExpectedFile('basic5/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic5/manifest.json'), $this->getExpectedFile('basic5/manifest.json'));
        $this->assertFileDoesNotExist($this->getPublicFolder('basic5/app.css.map'));
    }

    public function testInlineSitemaps()
    {
        $compiler = $this->createCompiler([
            'sourcemaps' => 'inline'
        ], $this->getDefaultPlugins(15000));
        $compiler->compile(['basic1/app.scss' => 'basic6/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic6/app.css'), $this->getExpectedFile('basic6/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic6/manifest.json'), $this->getExpectedFile('basic6/manifest.json'));
        $this->assertFileDoesNotExist($this->getPublicFolder('basic6/app.css.map'));
    }

    public function testNoManifest()
    {
        $compiler = $this->createCompiler([
            'manifest' => false
        ], $this->getDefaultPlugins(15000, false));
        $compiler->compile(['basic1/app.scss' => 'basic7/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic7/app.css'), $this->getExpectedFile('basic7/app.css'));
        $this->assertFileDoesNotExist($this->getPublicFolder('basic7/manifest.json'));
    }

    public function testChangeManifestName()
    {
        $compiler = $this->createCompiler([], $this->getDefaultPlugins(15000, false));
        $compiler->addPlugin(new JsonManifest([
            'name' => 'newManifestName'
        ]));
        $compiler->compile(['basic1/app.scss' => 'basic8/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic8/app.css'), $this->getExpectedFile('basic8/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic8/newManifestName.json'), $this->getExpectedFile('basic8/newManifestName.json'));
    }

    public function testWithHash()
    {
        $compiler = $this->createCompiler([
            'fileName' => '[name].[hash]'
        ], $this->getDefaultPlugins(15000));
        $compiler->compile(['basic1/app.scss' => 'basic9/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic9/app.71db82a2.css'), $this->getExpectedFile('basic9/app.71db82a2.css'));
        $this->assertFileEquals($this->getPublicFolder('basic9/manifest.json'), $this->getExpectedFile('basic9/manifest.json'));
        $this->assertFileExists($this->getPublicFolder('basic9/app.71db82a2.css.map'));
    }

    public function testMinified()
    {
        $compiler = $this->createCompiler([
            'style' => 'minified',
        ], $this->getDefaultPlugins(15000));
        $compiler->compile(['basic1/app.scss' => 'basic10/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic10/app.css'), $this->getExpectedFile('basic10/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic10/manifest.json'), $this->getExpectedFile('basic10/manifest.json'));
        $this->assertFileExists($this->getPublicFolder('basic10/app.css.map'));
    }

    public function testMinifiedWithAssets()
    {
        $compiler = $this->createCompiler([
            'style' => 'minified'
        ], $this->getDefaultPlugins(15000));
        $compiler->compile(['basic3/app.scss' => 'basic11/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('basic11/app.css'), $this->getExpectedFile('basic11/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic11/manifest.json'), $this->getExpectedFile('basic11/manifest.json'));
        $this->assertFileExists($this->getPublicFolder('basic11/app.css.map'));
    }
}