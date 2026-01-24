<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PublishAssetsCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'lartrix:publish-assets 
                            {--no-clean : 不清空目标目录}';

    /**
     * 命令描述
     */
    protected $description = '发布/更新 Lartrix 前端资源到 public/admin 目录（默认清空并强制覆盖）';

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $targetPath = public_path('admin');
        $noClean = $this->option('no-clean');

        $this->info('开始发布前端资源...');
        $this->newLine();

        // 默认清空目标目录，除非指定 --no-clean
        if (!$noClean && File::isDirectory($targetPath)) {
            $this->warn('正在清空目标目录: ' . $targetPath);
            File::deleteDirectory($targetPath);
            $this->info('目标目录已清空。');
        }

        // 发布前端资源（强制覆盖）
        $this->info('正在发布前端资源...');
        
        $result = Artisan::call('vendor:publish', [
            '--tag' => 'lartrix-assets',
            '--force' => true,
        ]);

        if ($result === 0) {
            $this->newLine();
            $this->info('========================================');
            $this->info('       前端资源发布完成！');
            $this->info('========================================');
            $this->newLine();
            
            $this->table(
                ['项目', '值'],
                [
                    ['目标目录', $targetPath],
                    ['清空目录', $noClean ? '否' : '是'],
                ]
            );

            $this->newLine();
            $this->info('前端资源已更新，请刷新浏览器查看效果。');

            return self::SUCCESS;
        }

        $this->error('前端资源发布失败。');
        return self::FAILURE;
    }
}
