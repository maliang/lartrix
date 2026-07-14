<?php

namespace Lartrix\Tests\Feature;

use PHPUnit\Framework\TestCase;

class ModuleMakeCommandTest extends TestCase
{
    /** @test */
    public function it_provides_a_valid_standard_module_manifest_stub(): void
    {
        $root = dirname(__DIR__, 2);
        $stub = file_get_contents($root . '/stubs/module/module.json.stub');
        $manifest = json_decode(str_replace([
            '{{MODULE_NAME}}',
            '{{LOWER_NAME}}',
            '{{REGISTRY_ID}}',
            '{{TITLE}}',
            '{{DESCRIPTION}}',
            '{{TYPE}}',
            '{{AUTHOR}}',
            '{{AUTHOR_URL}}',
        ], [
            'SpecBlog',
            'specblog',
            'official.spec-blog',
            'Spec Blog',
            'Spec blog module',
            'native',
            'Spec Author',
            'https://example.test/spec-author',
        ], $stub), true);

        $this->assertIsArray($manifest);
        $this->assertSame('trix.module.v1', $manifest['trix']['schema_version']);
        $this->assertSame('official.spec-blog', $manifest['trix']['id']);
        $this->assertSame('SpecBlog', $manifest['name']);
        $this->assertSame('specblog', $manifest['alias']);
        $this->assertSame('Spec Author', $manifest['trix']['author']);
        $this->assertSame('https://example.test/spec-author', $manifest['trix']['author_url']);
        $this->assertSame('resources/module/logo.svg', $manifest['trix']['logo']);
        $this->assertSame('resources/module/thumbnail.svg', $manifest['trix']['thumbnail']);
        $this->assertSame('php', $manifest['trix']['adapter']['language']);
        $this->assertSame('laravel', $manifest['trix']['adapter']['framework']);
        $this->assertFileExists($root . '/stubs/module/logo.svg.stub');
        $this->assertFileExists($root . '/stubs/module/thumbnail.svg.stub');
    }
}
