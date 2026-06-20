<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Lartrix\Services\ModuleService;

class ModuleInstallCommand extends Command
{
    protected $signature = 'lartrix:module-install {name : Module name}';
    protected $description = 'Install a module (migrate + seed + register menus/permissions)';

    public function handle(ModuleService $moduleService): int
    {
        $name = $this->argument('name');

        $this->info("Installing module: {$name}...");

        $result = $moduleService->install($name);

        if (!$result) {
            $this->error("Module [{$name}] installation failed.");
            return 1;
        }

        $this->info("Module [{$name}] installed successfully.");
        return 0;
    }
}
