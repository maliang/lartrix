<?php

namespace Lartrix\Modules\Registry;

/** 将通过校验的暂存包复制到显式目标目录，默认保留已经存在的模块。 */
class RegistryStagedPackageInstaller
{
    /**
     * 执行模块或项目安装流程。
     * @return array<string, mixed>
     */
    public function install(string $stagePath, string $manifest, string $targetPath): array
    {
        $manifestPath = $stagePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $manifest);
        if (!is_file($manifestPath)) {
            return $this->failure('manifest_missing', 'Staged package manifest file does not exist.');
        }

        $sourcePath = dirname($manifestPath);
        if (!is_dir($sourcePath)) {
            return $this->failure('source_missing', 'Staged package source directory does not exist.');
        }

        if (file_exists($targetPath)) {
            return $this->failure('target_exists', 'Target module directory already exists.');
        }

        $targetParent = dirname($targetPath);
        if (!is_dir($targetParent) && !mkdir($targetParent, 0775, true) && !is_dir($targetParent)) {
            return $this->failure('target_parent_create_failed', 'Target parent directory could not be created.');
        }

        if (!$this->copyDirectory($sourcePath, $targetPath)) {
            $this->removeDirectory($targetPath);
            return $this->failure('copy_failed', 'Staged package could not be copied completely.');
        }

        return [
            'installed' => true,
            'reason' => null,
            'message' => 'Staged package copied to local module directory.',
            'path' => $targetPath,
        ];
    }

        /** 递归复制目录及其文件。 */
    private function copyDirectory(string $source, string $target): bool
    {
        if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
            return false;
        }
        $items = scandir($source);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
            $targetPath = $target . DIRECTORY_SEPARATOR . $item;

            if (is_link($sourcePath)) {
                return false;
            }

            if (is_dir($sourcePath)) {
                if (!$this->copyDirectory($sourcePath, $targetPath)) {
                    return false;
                }
                continue;
            }

            if (!copy($sourcePath, $targetPath)) {
                return false;
            }
        }

        return true;
    }

    /** 清理复制失败后产生的不完整目标目录。 */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) { return; }
        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') { continue; }
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($directory);
    }

    /**
     * 执行 failure 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function failure(string $reason, string $message): array
    {
        return [
            'installed' => false,
            'reason' => $reason,
            'message' => $message,
            'path' => null,
        ];
    }
}
