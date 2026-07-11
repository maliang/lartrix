<?php

namespace Lartrix\Modules\Registry;

use JsonException;

/** 以 JSONL 记录模块更新计划和执行结果，便于追踪与后台展示。 */
class RegistryModuleUpdateAuditLogger
{
    /**
     * 向审计日志追加记录。
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public function append(string $path, array $record): array
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $this->failure('audit_directory_unwritable', 'Audit log directory could not be created.');
        }

        $record['recorded_at'] = gmdate('c');

        try {
            $line = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            return $this->failure('audit_json_encode_failed', 'Audit record could not be encoded as JSON.');
        }

        if (file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            return $this->failure('audit_write_failed', 'Audit log could not be written.');
        }

        return [
            'written' => true,
            'reason' => null,
            'message' => 'Audit record written.',
            'path' => $path,
        ];
    }

    /**
     * 执行 failure 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function failure(string $reason, string $message): array
    {
        return [
            'written' => false,
            'reason' => $reason,
            'message' => $message,
            'path' => null,
        ];
    }
}
