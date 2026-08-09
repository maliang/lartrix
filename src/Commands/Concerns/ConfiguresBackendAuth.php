<?php

namespace Lartrix\Commands\Concerns;

use Illuminate\Support\Facades\File;

/**
 * 后台模块的 auth guard/provider 配置逻辑。
 * 供 make-backend 与 backend-install 复用，幂等：已存在的 guard/provider 跳过。
 */
trait ConfiguresBackendAuth
{
    /**
     * 在 config/auth.php 的 guards/providers 中添加后台模块的 guard 与 provider。
     *
     * @return bool 是否发生了写入（true=已修改文件，false=无需修改或文件不存在）
     */
    protected function configureBackendAuth(string $guard, string $moduleName): bool
    {
        $authPath = config_path('auth.php');

        if (!File::exists($authPath)) {
            return false;
        }

        $content = File::get($authPath);
        $modelClass = "Modules\\{$moduleName}\\Models\\{$moduleName}";
        $changed = false;

        // 添加 guard
        if (!str_contains($content, "'{$guard}' =>")) {
            $guardBlock = "\n        '{$guard}' => [\n            'driver' => 'sanctum',\n            'provider' => '{$guard}s',\n        ],";
            $newContent = $this->insertIntoArraySection($content, 'guards', $guardBlock);
            if ($newContent !== false) {
                $content = $newContent;
                $changed = true;
            }
        }

        // 添加 provider
        $providerName = $guard . 's';
        if (!str_contains($content, "'{$providerName}' =>")) {
            $providerBlock = "\n        '{$providerName}' => [\n            'driver' => 'eloquent',\n            'model' => \\{$modelClass}::class,\n        ],";
            $newContent = $this->insertIntoArraySection($content, 'providers', $providerBlock);
            if ($newContent !== false) {
                $content = $newContent;
                $changed = true;
            }
        }

        if ($changed) {
            File::put($authPath, $content);
        }

        return $changed;
    }

    /**
     * 在 auth.php 的指定数组段落末尾插入内容。
     * 通过逐字符解析括号匹配，找到 'key' => [ ... ] 中最后一个子项 ], 的位置。
     */
    protected function insertIntoArraySection(string $content, string $sectionKey, string $insertBlock): string|false
    {
        // 找到 'guards' => [ 或 'providers' => [ 的位置
        $pattern = "/'{$sectionKey}'\s*=>\s*\[/";
        if (!preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        // 找到开头 [ 的位置
        $openBracketPos = strpos($content, '[', $match[0][1]);
        if ($openBracketPos === false) {
            return false;
        }

        // 从 [ 开始，用括号计数找到匹配的 ]
        $depth = 0;
        $closeBracketPos = null;
        $len = strlen($content);

        for ($i = $openBracketPos; $i < $len; $i++) {
            if ($content[$i] === '[') {
                $depth++;
            } elseif ($content[$i] === ']') {
                $depth--;
                if ($depth === 0) {
                    $closeBracketPos = $i;
                    break;
                }
            }
        }

        if ($closeBracketPos === null) {
            return false;
        }

        // 在闭合 ] 前插入新内容
        $before = substr($content, 0, $closeBracketPos);
        $after = substr($content, $closeBracketPos);

        return $before . $insertBlock . "\n    " . $after;
    }
}
