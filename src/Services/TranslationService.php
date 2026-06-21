<?php

namespace Lartrix\Services;

use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module as ModuleFacade;

/**
 * TranslationService - 多语言翻译服务
 *
 * 负责合并包内语言包、项目语言包和模块语言包，
 * 提供统一的翻译 API 供前端消费。
 */
class TranslationService
{
    /**
     * 获取指定 locale 的全量翻译
     *
     * @param string $locale 语言代码，如 zh-CN、en-US
     * @return array 合并后的翻译数组
     */
    public function getTranslations(string $locale = 'zh-CN'): array
    {
        // 1. 包内语言包作为基础
        $translations = $this->loadPackageTranslations($locale);

        // 2. 合并项目 lang/vendor/lartrix/ 下的覆盖
        $projectLang = $this->loadProjectTranslations($locale);
        $translations = array_replace_recursive($translations, $projectLang);

        // 3. 合并模块语言包（已启用模块）
        $moduleTranslations = $this->loadModuleTranslations($locale);
        $translations = array_replace_recursive($translations, $moduleTranslations);

        return $translations;
    }

    /**
     * 加载包内语言包
     */
    protected function loadPackageTranslations(string $locale): array
    {
        // Laravel 语言包路径：lang/zh-CN.php
        $file = __DIR__ . '/../../lang/' . $locale . '.php';
        if (!file_exists($file)) {
            // fallback 到英文
            $file = __DIR__ . '/../../lang/en-US.php';
        }
        if (!file_exists($file)) {
            return [];
        }
        $translations = include $file;
        return is_array($translations) ? $translations : [];
    }

    /**
     * 加载项目 lang/vendor/lartrix/ 下的语言覆盖
     */
    protected function loadProjectTranslations(string $locale): array
    {
        $file = lang_path('vendor/lartrix/' . $locale . '.php');
        if (!file_exists($file)) {
            return [];
        }
        $translations = include $file;
        return is_array($translations) ? $translations : [];
    }

    /**
     * 加载所有已启用模块的语言包
     */
    protected function loadModuleTranslations(string $locale): array
    {
        $translations = [];

        try {
            // 获取所有已启用的模块
            $modules = ModuleFacade::allEnabled();
        } catch (\Throwable $e) {
            return [];
        }

        foreach ($modules as $moduleName => $module) {
            // nwidart/laravel-modules 的标准语言包路径：Modules/{Module}/Resources/lang/{locale}
            $langPath = module_path($moduleName) . '/Resources/lang/' . $locale . '.php';
            
            if (file_exists($langPath)) {
                $moduleLang = include $langPath;
                if (is_array($moduleLang)) {
                    $translations = array_replace_recursive($translations, $moduleLang);
                }
            }
        }

        return $translations;
    }
}
