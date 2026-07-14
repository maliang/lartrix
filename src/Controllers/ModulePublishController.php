<?php

namespace Lartrix\Controllers;

use Lartrix\Services\ModulePublishService;

/** 承接本地模块与项目发布路由，隔离发布权限和打包职责。 */
class ModulePublishController extends Controller
{
    /** 注入模块发布应用服务。 */
    public function __construct(private readonly ModulePublishService $publishing)
    {
    }

    /** 发布指定本地模块。 */
    public function module(string $name): array
    {
        return $this->publishing->publishLocal($name);
    }

    /** 发布当前项目。 */
    public function project(): array
    {
        return $this->publishing->publishLocalProject();
    }
}
