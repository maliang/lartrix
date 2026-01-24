<?php

namespace Lartrix\Controllers;

use Lartrix\Services\ModuleService;
use function Lartrix\Support\success;
use function Lartrix\Support\error;

class ModuleController extends Controller
{
    public function __construct(
        protected ModuleService $moduleService
    ) {}

    /**
     * 模块列表
     */
    public function index(): array
    {
        $modules = $this->moduleService->getModules();
        return success($modules);
    }

    /**
     * 启用模块
     */
    public function enable(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error('模块不存在', null, 40102);
        }

        $result = $this->moduleService->enable($name);

        if (!$result) {
            error('启用失败', null, 40000);
        }

        return success('启用成功');
    }

    /**
     * 禁用模块
     */
    public function disable(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error('模块不存在', null, 40102);
        }

        $result = $this->moduleService->disable($name);

        if (!$result) {
            error('禁用失败', null, 40000);
        }

        return success('禁用成功');
    }
}
