<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Services\ModuleMarketService;
use Lartrix\Schema\Pages\ModuleMarketSchema;

/** 承接模块市场查询与安装路由，隔离市场接口职责。 */
class ModuleMarketController extends Controller
{
    /** 注入模块市场应用服务。 */
    public function __construct(
        private readonly ModuleMarketService $market,
        private readonly ModuleMarketSchema $schema,
    ) {
    }

    /** 返回独立模块市场页面 Schema。 */
    public function ui(): array
    {
        return $this->schema->market();
    }

    /** 返回可安装模块列表。 */
    public function modules(Request $request): array
    {
        return $this->market->marketModules($request);
    }

    /** 返回可安装项目列表。 */
    public function projects(Request $request): array
    {
        return $this->market->marketProjects($request);
    }

    /** 下载并暂存指定市场模块。 */
    public function installModule(string $id): array
    {
        return $this->market->installMarketModule($id);
    }

    /** 获取指定项目的安装计划。 */
    public function installProject(string $id): array
    {
        return $this->market->installMarketProject($id);
    }
}
