<?php

namespace Lartrix\Modules\Registry;

/** 在显式确认后备份旧模块并以暂存版本替换，避免更新过程直接破坏现有目录。 */
class RegistryModuleReplacer
{
    /**
     * 备份旧目录并替换为新版本。
     * @return array<string, mixed>
     */
    public function replace(string $sourcePath, string $targetPath, string $backupPath, bool $confirmed): array
    {
        if (!$confirmed) {
            return $this->failure('confirmation_required', 'Explicit confirmation is required before replacing a module directory.');
        }

        if (!is_dir($sourcePath)) {
            return $this->failure('source_missing', 'Source module directory does not exist.');
        }

        if (!is_dir($targetPath)) {
            return $this->failure('target_missing', 'Target module directory does not exist.');
        }

        if (file_exists($backupPath)) {
            return $this->failure('backup_exists', 'Backup directory already exists.');
        }

        $backupParent = dirname($backupPath);
        if (!is_dir($backupParent)) {
            mkdir($backupParent, 0775, true);
        }

        if (!rename($targetPath, $backupPath)) {
            return $this->failure('backup_failed', 'Target module directory could not be moved to backup.');
        }

        if (!rename($sourcePath, $targetPath)) {
            if (is_dir($backupPath) && !file_exists($targetPath)) {
                rename($backupPath, $targetPath);
            }

            return $this->failure('replace_failed', 'Source module directory could not replace target directory; rollback was attempted.');
        }

        return [
            'replaced' => true,
            'reason' => null,
            'message' => 'Module directory replaced; previous version was moved to backup.',
            'target_path' => $targetPath,
            'backup_path' => $backupPath,
        ];
    }

    /**
     * 执行 failure 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function failure(string $reason, string $message): array
    {
        return [
            'replaced' => false,
            'reason' => $reason,
            'message' => $message,
            'target_path' => null,
            'backup_path' => null,
        ];
    }
}
