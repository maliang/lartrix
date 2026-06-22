<?php

namespace Lartrix\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Nwidart\Modules\Facades\Module as ModuleFacade;

/**
 * TranslationService - 多语言翻译服务
 *
 * 负责合并包内语言包、项目语言包和模块语言包，
 * 提供统一的翻译 API 供前端消费。
 */
class TranslationService
{
    public function getLanguages(): array
    {
        return config('lartrix.languages', [
            'zh-CN' => ['label' => '中文', 'file' => 'zh-CN', 'naive_locale' => 'zh-CN'],
            'en-US' => ['label' => 'English', 'file' => 'en-US', 'naive_locale' => 'en-US'],
        ]);
    }

    public function normalizeLocale(string $locale): ?string
    {
        $normalized = strtolower(str_replace('_', '-', $locale));
        foreach (array_keys($this->getLanguages()) as $code) {
            if (strtolower(str_replace('_', '-', $code)) === $normalized) {
                return $code;
            }
        }

        return null;
    }

    public function getLanguageOptions(): array
    {
        $options = [];
        foreach ($this->getLanguages() as $code => $language) {
            $options[] = [
                'label' => $language['label'] ?? $code,
                'key' => $code,
                'naiveLocale' => $language['naive_locale'] ?? 'en-US',
            ];
        }

        return $options;
    }

    protected function getLanguageFileCode(string $locale): string
    {
        $canonical = $this->normalizeLocale($locale)
            ?? $this->normalizeLocale((string) config('lartrix.fallback_locale', 'en-US'));
        $canonical ??= array_key_first($this->getLanguages()) ?: 'en-US';

        return $this->getLanguages()[$canonical]['file']
            ?? $canonical;
    }

    /**
     * 获取指定 locale 的全量翻译
     *
     * @param string $locale 语言代码，如 zh-CN、en-US
     * @return array 合并后的翻译数组
     */
    public function getTranslations(string $locale = 'zh-CN'): array
    {
        $locale = $this->getLanguageFileCode($locale);

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

    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $this->normalizeLocale($locale ?? app()->getLocale())
            ?? $this->normalizeLocale((string) config('lartrix.locale', 'zh-CN'));
        $locale ??= array_key_first($this->getLanguages()) ?: 'en-US';

        $fallback = $this->normalizeLocale((string) config('lartrix.fallback_locale', 'en-US'));
        foreach (array_unique(array_filter([$locale, $fallback])) as $catalogLocale) {
            Lang::addLines($this->flatten($this->getTranslations($catalogLocale)), $catalogLocale, 'lartrix-runtime');
        }

        $translationKey = 'lartrix-runtime::' . $key;
        $value = Lang::get($translationKey, $replace, $locale, false);
        if ($value === $translationKey && $fallback !== null && $fallback !== $locale) {
            $value = Lang::get($translationKey, $replace, $fallback, false);
        }

        return is_string($value) && $value !== $translationKey ? $value : $key;
    }

    protected function flatten(array $translations, string $prefix = ''): array
    {
        $lines = [];
        foreach ($translations as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $lines += $this->flatten($value, $path);
            } else {
                $lines[$path] = $value;
            }
        }

        return $lines;
    }

    /**
     * 加载包内语言包
     */
    protected function loadPackageTranslations(string $locale): array
    {
        $translations = [];
        $files = [
            __DIR__ . '/../../lang/' . $locale . '.php',
            __DIR__ . '/../../lang/' . $locale . '/lartrix.php',
            __DIR__ . '/../../lang/' . $locale . '-extra.php',
        ];
        foreach ($files as $file) {
            if (!File::exists($file)) {
                continue;
            }
            $loaded = File::getRequire($file);
            if (is_array($loaded)) {
                $translations = array_replace_recursive($translations, $loaded);
            }
        }

        return $translations;
    }

    /**
     * 加载项目 lang/vendor/lartrix/ 下的语言覆盖
     */
    protected function loadProjectTranslations(string $locale): array
    {
        $translations = [];
        $files = [
            lang_path('vendor/lartrix/' . $locale . '/lartrix.php'),
            lang_path('vendor/lartrix/' . $locale . '.php'),
        ];
        foreach ($files as $file) {
            if (!File::exists($file)) {
                continue;
            }
            $loaded = File::getRequire($file);
            if (is_array($loaded)) {
                $translations = array_replace_recursive($translations, $loaded);
            }
        }

        return $translations;
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
            $modulePath = method_exists($module, 'getPath') ? $module->getPath() : module_path($moduleName);
            $moduleKey = method_exists($module, 'getLowerName')
                ? $module->getLowerName()
                : strtolower((string) $moduleName);
            $relativeLangPath = config('modules.paths.generator.lang.path', 'Resources/lang');
            $langRoots = [
                $modulePath . '/' . trim($relativeLangPath, '/\\'),
                resource_path('lang/modules/' . $moduleKey),
            ];

            foreach ($langRoots as $langRoot) {
                // 兼容 Lartrix 早期约定的单文件语言包。
                $legacyFile = $langRoot . '/' . $locale . '.php';
                if (File::exists($legacyFile)) {
                    $legacy = File::getRequire($legacyFile);
                    if (is_array($legacy)) {
                        $translations = array_replace_recursive($translations, $legacy);
                    }
                }

                // Nwidart 标准目录：{locale}/{group}.php，对应 module::group.key。
                $localeDirectory = $langRoot . '/' . $locale;
                if (!File::isDirectory($localeDirectory)) {
                    continue;
                }
                foreach (File::files($localeDirectory) as $file) {
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }
                    $group = $file->getFilenameWithoutExtension();
                    $lines = File::getRequire($file->getPathname());
                    if (is_array($lines)) {
                        $translations[$moduleKey][$group] = array_replace_recursive(
                            $translations[$moduleKey][$group] ?? [],
                            $lines
                        );
                    }
                }
            }
        }

        return $translations;
    }
}
