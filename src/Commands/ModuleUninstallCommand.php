<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Lartrix\Services\ModuleService;
use Lartrix\Models\Module;

class ModuleUninstallCommand extends Command
{
    protected $signature = 'lartrix:module-uninstall {name?* : 模块名称，留空则卸载全部已启用模块}';
    protected $description = '卸载模块。不带参数时卸载所有已启用的模块。';

    public function handle(ModuleService $moduleService): int
    {
        $names = $this->argument('name');

        if (empty($names)) {
            $allModules = Module::where('enabled', true)->get();
            foreach ($allModules as $m) {
                $this->uninstallSingle($m->name, $moduleService);
            }
            return 0;
        }

        foreach ($names as $name) {
            $this->uninstallSingle($name, $moduleService);
        }

        return 0;
    }

    protected function uninstallSingle(string $name, ModuleService $moduleService): void
    {
        $this->info("Uninstalling module: {$name}...");

        if ($moduleService->uninstall($name)) {
            $this->info("Module [{$name}] uninstalled successfully.");
        } else {
            $this->error("Module [{$name}] uninstallation failed.");
        }
    }
}
