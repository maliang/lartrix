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
        if (!is_dir($targetParent)) {
            mkdir($targetParent, 0775, true);
        }

        $this->copyDirectory($sourcePath, $targetPath);

        return [
            'installed' => true,
            'reason' => null,
            'message' => 'Staged package copied to local module directory.',
            'path' => $targetPath,
        ];
    }

        /** 递归复制目录及其文件。 */
    private function copyDirectory(string $source, string $target): void
    {
        mkdir($target, 0775, true);
        $items = scandir($source);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
            $targetPath = $target . DIRECTORY_SEPARATOR . $item;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $targetPath);
                continue;
            }

            copy($sourcePath, $targetPath);
        }
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
