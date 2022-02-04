<?php

namespace Ryssbowh\ScssPhp\Tests;

use ScssPhp\ScssPhp\Exception\CompilerException;

class ImportsTest extends BaseTest
{
    public function testImportFail()
    {
        $this->expectException(CompilerException::class);
        $this->createCompiler()->compile(['imports1/app.scss' => 'imports1/app.css'], $this->getSrcFolder());
    }

    public function testImportPaths()
    {
        $compiler = $this->createCompiler();
        $compiler->prependImportPaths($this->getSrcFolder('imported'));
        $compiler->compile(['imports1/app.scss' => 'imports2/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('imports2/app.css'), $this->getExpectedFile('imports1/app.css'));
        $this->assertFileEquals($this->getPublicFolder('imports2/manifest.json'), $this->getExpectedFile('imports1/manifest.json'));
    }

    public function testAliases()
    {
        $compiler = $this->createCompiler();
        $compiler->addAlias('#', 'aliased');
        $compiler->compile(['imports3/app.scss' => 'imports3/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('imports3/app.css'), $this->getExpectedFile('imports3/app.css'));
        $this->assertFileEquals($this->getPublicFolder('imports3/manifest.json'), $this->getExpectedFile('imports3/manifest.json'));
    }

    public function testAliasesAndImport()
    {
        $compiler = $this->createCompiler();
        $compiler
            ->prependImportPaths($this->getSrcFolder('imported'))
            ->addAlias('#', 'aliased');
        $compiler->compile(['imports4/app.scss' => 'imports4/app.css'], $this->getSrcFolder());
        $this->assertFileEquals($this->getPublicFolder('imports4/app.css'), $this->getExpectedFile('imports4/app.css'));
        $this->assertFileEquals($this->getPublicFolder('imports4/manifest.json'), $this->getExpectedFile('imports4/manifest.json'));
    }

    public function testFakeSourceImport()
    {
        $compiler = $this->createCompiler();
        $compiler->compile([$this->getSrcFolder('faked/app.scss') => 'imports5/app.css'], $this->getSrcFolder(), $this->getSrcFolder('imports5/template.twig'));
        $this->assertFileEquals($this->getPublicFolder('basic5/app.css'), $this->getExpectedFile('basic5/app.css'));
        $this->assertFileEquals($this->getPublicFolder('basic5/manifest.json'), $this->getExpectedFile('basic5/manifest.json'));
    }
}