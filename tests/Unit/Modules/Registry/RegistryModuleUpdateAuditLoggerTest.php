<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryModuleUpdateAuditLogger;
use PHPUnit\Framework\TestCase;

class RegistryModuleUpdateAuditLoggerTest extends TestCase
{
    public function testAppendsJsonLineUpdateAuditRecord(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-update-audit-' . uniqid('', true) . DIRECTORY_SEPARATOR . 'updates.jsonl';

        $result = (new RegistryModuleUpdateAuditLogger())->append($path, [
            'event' => 'updated',
            'module_id' => 'official.cms',
            'language' => 'php',
            'framework' => 'laravel',
            'current_version' => '1.0.0',
            'target_version' => '1.1.0',
            'target_path' => '/app/Modules/OfficialCms',
            'backup_path' => '/app/Modules/.backup/OfficialCms-1.0.0',
            'security' => ['writes_files' => true],
        ]);

        self::assertTrue($result['written']);
        self::assertFileExists($path);

        $records = file($path, FILE_IGNORE_NEW_LINES);
        self::assertCount(1, $records);

        $record = json_decode((string) $records[0], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('updated', $record['event']);
        self::assertSame('official.cms', $record['module_id']);
        self::assertSame('php', $record['language']);
        self::assertSame('laravel', $record['framework']);
        self::assertSame('1.0.0', $record['current_version']);
        self::assertSame('1.1.0', $record['target_version']);
        self::assertIsString($record['recorded_at']);
    }
}
