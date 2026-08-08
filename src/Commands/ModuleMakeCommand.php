<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/** 生成符合 Trix 协议和 Lartrix 模块约定的标准模块骨架。 */
class ModuleMakeCommand extends Command
{
    protected $signature = 'lartrix:module-make
                            {name : 模块名称，例如 Blog}
                            {--id= : Trix 市场 id，默认为模块别名}
                            {--title= : 模块可读标题}
                            {--description= : 模块描述}
                            {--type=native : Trix 模块类型}
                            {--author= : 模块作者，通常为 Auth Key 用户名或邮箱}
                            {--author-url= : 模块作者 URL}
                            {--force : 模块已存在时覆盖 Lartrix 标准文件}';

    protected $description = '创建基于 nwidart/laravel-modules 的标准 Lartrix/Trix 模块';

    private string $moduleName;
    private string $lowerName;

    /** 处理命令或请求的主流程。 */
    public function handle(): int
    {
        $this->moduleName = Str::studly((string) $this->argument('name'));
        $this->lowerName = Str::lower($this->moduleName);

        $modulePath = $this->modulePath();
        $exists = File::isDirectory($modulePath);

        if ($exists && !$this->option('force')) {
            $this->error("Module [{$this->moduleName}] already exists. Use --force to refresh Lartrix standard files.");

            return self::FAILURE;
        }

        if (!$exists) {
            $this->info("Creating base module [{$this->moduleName}] with module:make...");
            try {
                $result = Artisan::call('module:make', [
                    'name' => [$this->moduleName],
                    '--force' => (bool) $this->option('force'),
                ]);
            } catch (ProcessTimedOutException $e) {
                if (!File::isDirectory($modulePath)) {
                    throw $e;
                }

                $result = 0;
                $this->warn('module:make created the module, but composer dump-autoload timed out.');
                $this->line('Run composer dump-autoload manually after this command finishes.');
            }

            if ($result !== 0) {
                $this->error('module:make failed.');
                $this->line(Artisan::output());

                return self::FAILURE;
            }
        } else {
            $this->warn("Refreshing Lartrix standard files for existing module [{$this->moduleName}].");
        }

        $this->writeStandardFiles();

        $this->info("Standard Lartrix module [{$this->moduleName}] is ready.");
        $this->line('Manifest: ' . $this->modulePath('module.json'));
        $this->line('Logo: ' . $this->modulePath('resources/module/logo.svg'));
        $this->line('Thumbnail: ' . $this->modulePath('resources/module/thumbnail.svg'));

        return self::SUCCESS;
    }

        /** 将数据写入指定存储位置。 */
    private function writeStandardFiles(): void
    {
        foreach ($this->standardFiles() as $stub => $target) {
            $content = File::get($this->stubPath($stub));
            $content = str_replace(
                array_keys($this->replacements()),
                array_values($this->replacements()),
                $content
            );

            $targetPath = $this->modulePath($target);
            File::ensureDirectoryExists(dirname($targetPath));
            File::put($targetPath, $content);
        }
    }

    /**
     * 执行 standardFiles 方法对应的具体职责。
     * @return array<string, string>
     */
    private function standardFiles(): array
    {
        return [
            'module.json.stub' => 'module.json',
            'logo.svg.stub' => 'resources/module/logo.svg',
            'thumbnail.svg.stub' => 'resources/module/thumbnail.svg',
        ];
    }

    /**
     * 备份旧目录并替换为新版本。
     * @return array<string, string>
     */
    private function replacements(): array
    {
        $title = trim((string) ($this->option('title') ?: $this->moduleName));
        $description = trim((string) ($this->option('description') ?: "{$title} module"));
        $id = trim((string) ($this->option('id') ?: $this->lowerName));
        $type = trim((string) ($this->option('type') ?: 'native'));
        $author = trim((string) ($this->option('author') ?: ''));
        $authorUrl = trim((string) ($this->option('author-url') ?: ''));

        return [
            '{{MODULE_NAME}}' => $this->moduleName,
            '{{LOWER_NAME}}' => $this->lowerName,
            '{{REGISTRY_ID}}' => $id,
            '{{TITLE}}' => $title,
            '{{DESCRIPTION}}' => $description,
            '{{TYPE}}' => $type,
            '{{AUTHOR}}' => $author,
            '{{AUTHOR_URL}}' => $authorUrl,
        ];
    }

        /** 执行 modulePath 方法对应的具体职责。 */
    private function modulePath(string $path = ''): string
    {
        $basePath = base_path('Modules/' . $this->moduleName);

        return $path === '' ? $basePath : $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /** 解析模块生成器使用的 Stub 模板路径。 */
    private function stubPath(string $stub): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'module' . DIRECTORY_SEPARATOR . $stub;
    }
}
