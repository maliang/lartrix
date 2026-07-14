<?php

namespace Lartrix\Services;

/** 组合本地模块投影与发布状态，供模块管理列表使用。 */
class ModuleManagementService
{
    /** 注入本地模块和发布状态服务。 */
    public function __construct(
        private readonly ModuleService $modules,
        private readonly ModulePublishService $publishing,
    ) {
    }

    /** 返回包含发布状态的本地模块列表。 */
    public function modules(): array
    {
        return $this->publishing->withPublishState($this->modules->getModules());
    }
}
