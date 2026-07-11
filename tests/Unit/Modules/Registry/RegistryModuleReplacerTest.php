<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryModuleReplacer;
use PHPUnit\Framework\TestCase;

class RegistryModuleReplacerTest extends TestCase
{
    public function testRequiresExplicitConfirmation(): void
    {
        $paths = $this->makeReplaceFixture();

        $result = (new RegistryModuleReplacer())->replace(
            $paths['source'],
            $paths['target'],
            $paths['backup'],
            false
        );

        self::assertFalse($result['replaced']);
        self::assertSame('confirmation_required', $result['reason']);
        self::assertFileExists($paths['target'] . DIRECTORY_SEPARATOR . 'old.txt');
    }

    public function testBacksUpOldModuleAndReplacesTargetDirectory(): void
    {
        $paths = $this->makeReplaceFixture();

        $result = (new RegistryModuleReplacer())->replace(
            $paths['source'],
            $paths['target'],
            $paths['backup'],
            true
        );

        self::assertTrue($result['replaced']);
        self::assertFileExists($paths['backup'] . DIRECTORY_SEPARATOR . 'old.txt');
        self::assertFileExists($paths['target'] . DIRECTORY_SEPARATOR . 'new.txt');
        self::assertDirectoryDoesNotExist($paths['source']);
    }

    public function testRejectsExistingBackupDirectory(): void
    {
        $paths = $this->makeReplaceFixture();
        mkdir($paths['backup'], 0775, true);

        $result = (new RegistryModuleReplacer())->replace(
            $paths['source'],
            $paths['target'],
            $paths['backup'],
            true
        );

        self::assertFalse($result['replaced']);
        self::assertSame('backup_exists', $result['reason']);
    }

    /**
     * @return array{source: string, target: string, backup: string}
     */
    private function makeReplaceFixture(): array
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-replace-' . uniqid('', true);
        $source = $root . DIRECTORY_SEPARATOR . 'OfficialCmsNext';
        $target = $root . DIRECTORY_SEPARATOR . 'OfficialCms';
        $backup = $root . DIRECTORY_SEPARATOR . 'OfficialCmsBackup';

        mkdir($source, 0775, true);
        mkdir($target, 0775, true);
        file_put_contents($source . DIRECTORY_SEPARATOR . 'new.txt', 'new');
        file_put_contents($target . DIRECTORY_SEPARATOR . 'old.txt', 'old');

        return ['source' => $source, 'target' => $target, 'backup' => $backup];
    }
}
