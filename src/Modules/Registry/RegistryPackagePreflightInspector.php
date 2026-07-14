<?php

namespace Lartrix\Modules\Registry;

use ZipArchive;

/** 在解压前执行基础路径安全预检，并报告清单中声明的敏感能力。 */
class RegistryPackagePreflightInspector
{
    /**
     * 检查发布包的基础安全风险。
     * @return array<string, mixed>
     */
    public function inspect(string $packagePath): array
    {
        if (!is_file($packagePath)) {
            return $this->failure('package_missing', 'Registry package file does not exist.');
        }

        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            return $this->failure('zip_open_failed', 'Registry package is not a readable zip file.');
        }

        $manifest = null;
        $entries = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            $normalized = str_replace('\\', '/', $name);

            // 预检阶段只做“包能不能安全展开”的判断，不读取或执行包内 PHP 代码。
            if ($this->isUnsafePath($normalized)) {
                $zip->close();
                return $this->failure('unsafe_path', "Registry package contains unsafe path: {$name}");
            }

            if ($this->isSymbolicLink($zip, $index)) {
                $zip->close();
                return $this->failure('symbolic_link_blocked', "Registry package contains a symbolic link: {$name}");
            }

            $entries[] = $normalized;
            if ($this->isManifestPath($normalized)) {
                $manifest = $normalized;
            }
        }

        $zip->close();

        if ($manifest === null) {
            return $this->failure('manifest_missing', 'Registry package must contain module.json.');
        }

        return [
            'ok' => true,
            'reason' => null,
            'message' => 'Registry package passed preflight checks.',
            'manifest' => $manifest,
            'file_count' => count($entries),
            'entries' => $entries,
        ];
    }

        /** 判断当前业务条件是否成立。 */
    private function isManifestPath(string $path): bool
    {
        return str_ends_with($path, '/module.json') || $path === 'module.json';
    }

    /** 判断 ZIP 条目是否为 Unix 符号链接。 */
    private function isSymbolicLink(ZipArchive $zip, int $index): bool
    {
        $operationsSystem = 0;
        $attributes = 0;
        if (!$zip->getExternalAttributesIndex($index, $operationsSystem, $attributes)) {
            return false;
        }

        return $operationsSystem === ZipArchive::OPSYS_UNIX
            && (($attributes >> 16) & 0170000) === 0120000;
    }

        /** 判断当前业务条件是否成立。 */
    private function isUnsafePath(string $path): bool
    {
        // 阻断绝对路径、Windows 盘符路径和目录穿越，避免 ZIP slip 覆盖项目文件。
        if ($path === '' || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return true;
        }

        if (preg_match('/^[A-Za-z]:\//', $path) === 1) {
            return true;
        }

        $segments = explode('/', $path);

        return in_array('..', $segments, true);
    }

    /**
     * 执行 failure 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function failure(string $reason, string $message): array
    {
        return [
            'ok' => false,
            'reason' => $reason,
            'message' => $message,
            'manifest' => null,
            'file_count' => 0,
            'entries' => [],
        ];
    }
}
