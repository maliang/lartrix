<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Lartrix\Services\ModuleService;
use Lartrix\Models\Module;

class ModuleInstallCommand extends Command
{
    protected $signature = 'lartrix:module-install {name?* : Module name(s), empty = all not installed}';
    protected $description = 'Install module(s). Without arguments, installs all uninstalled modules.';

    public function handle(ModuleService $moduleService): int
    {
        $names = $this->argument('name');

        if (empty($names)) {
            $allModules = Module::where('enabled', false)->get();
            foreach ($allModules as $m) {
                $this->installSingle($m->name, $moduleService);
            }
            return 0;
        }

        foreach ($names as $name) {
            $this->installSingle($name, $moduleService);
        }

        return 0;
    }

    protected function installSingle(string $name, ModuleService $moduleService): void
    {
        $this->info("Installing module: {$name}...");

        if ($moduleService->install($name)) {
            $this->info("Module [{$name}] installed successfully.");
        } else {
            $this->error("Module [{$name}] installation failed.");
        }
    }
}
