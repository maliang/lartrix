<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Lartrix\Services\ModuleService;

class ModuleUninstallCommand extends Command
{
    protected $signature = 'lartrix:module-uninstall {name : Module name}';
    protected $description = 'Uninstall a module (clean menus/permissions + rollback migration)';

    public function handle(ModuleService $moduleService): int
    {
        $name = $this->argument('name');

        $this->info("Uninstalling module: {$name}...");

        $result = $moduleService->uninstall($name);

        if (!$result) {
            $this->error("Module [{$name}] uninstallation failed.");
            return 1;
        }

        $this->info("Module [{$name}] uninstalled successfully.");
        return 0;
    }
}
