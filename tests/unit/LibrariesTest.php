<?php

namespace Ryssbowh\ScssPhp\Tests;

use Ryssbowh\ScssPhp\plugins\FileLoader;
use ScssPhp\ScssPhp\Exception\CompilerException;

class LibrariesTest extends BaseTest
{
    public function testFontawesome()
    {
        $compiler = $this->createCompiler();
        $compiler->compile(['library1/app.scss' => 'library1/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('library1/app.css'), $this->getExpectedFile('library1/app.css'));
        $this->assertFileEquals($this->getPublicFolder('library1/manifest.json'), $this->getExpectedFile('library1/manifest.json'));
    }

    public function testBootstrap()
    {
        $compiler = $this->createCompiler();
        $compiler->compile(['library2/app.scss' => 'library2/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('library2/app.css'), $this->getExpectedFile('library2/app.css'));
        $this->assertFileEquals($this->getPublicFolder('library2/manifest.json'), $this->getExpectedFile('library2/manifest.json'));
    }
}