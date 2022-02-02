<?php

namespace Ryssbowh\ScssPhp\Tests;

use Ryssbowh\ScssPhp\exceptions\NotFoundException;
use Ryssbowh\ScssPhp\exceptions\WrongParameterException;
use ScssPhp\ScssPhp\OutputStyle;

class CompilerTest extends BaseTest
{
    public function testOptions()
    {
        $compiler = $this->createCompiler([
            'sourcemaps' => 'file',
            'style' => OutputStyle::EXPANDED,
            'cleanDestination' => true,
            'fileName' => '[name].[hash]',
            'hashMethod' => 'crc32b',
            'manifest' => true,
            'manifestName' => 'manifest',
            'disableCache' => true,
            'forceCacheRefresh' => false,
            'cacheLifetime' => 604800,
            'cacheCheckImportResolutions' => false,
            'cacheOptions' => [],
            'cacheFolder' => __DIR__,
            'publicFolder' => $this->getPublicFolder()
        ]);
        $this->assertIsString($compiler->sourcemaps);
        $this->assertIsString($compiler->fileName);
        $this->assertIsString($compiler->hashMethod);
        $this->assertIsString($compiler->manifestName);
        $this->assertIsString($compiler->style);
        $this->assertIsBool($compiler->cleanDestination);
        $this->assertIsBool($compiler->manifest);
        $this->assertIsBool($compiler->disableCache);
        $this->assertIsBool($compiler->forceCacheRefresh);
        $this->assertIsBool($compiler->cacheCheckImportResolutions);
        $this->assertIsArray($compiler->cacheOptions);
    }

    public function testWrongPublicFolder()
    {
        $this->expectException(WrongParameterException::class);
        $compiler = $this->createCompiler();
        $compiler->publicFolder = null;
        $compiler->compile(['basic1/app.scss' => 'basic1.css'], $this->getSrcFolder());
    }

    public function testWrongSourcemaps()
    {
        $this->expectException(WrongParameterException::class);
        $compiler = $this->createCompiler();
        $compiler->sourcemaps = 'wrong';
        $compiler->compile(['basic1/app.scss' => 'basic1.css'], $this->getSrcFolder());
    }

    public function testWrongStyle()
    {
        $this->expectException(WrongParameterException::class);
        $this->createCompiler([
            'style' => 'wrong'
        ])->compile(['basic1/app.scss' => 'basic1.css'], $this->getSrcFolder());
    }

    public function testWrongHash()
    {
        $this->expectException(WrongParameterException::class);
        $this->createCompiler([
            'hashMethod' => 'wrong'
        ])->compile(['basic1/app.scss' => 'basic1.css'], $this->getSrcFolder());
    }

    public function testWrongCacheFolder()
    {
        $this->expectException(WrongParameterException::class);
        $compiler = $this->createCompiler();
        $compiler->cacheFolder = null;
        $compiler->compile(['basic1/app.scss' => 'basic1.css'], $this->getSrcFolder());
    }

    public function testWrongSource()
    {
        $this->expectException(NotFoundException::class);
        $this->createCompiler()->compile(['wrong.scss' => 'basic1.css'], $this->getSrcFolder());
    }
}