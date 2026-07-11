<?php

namespace Lartrix\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Lartrix\Models\Module;
use Symfony\Component\HttpFoundation\Response;

/** 在请求进入模块路由前统一校验模块启用状态。 */
class EnsureModuleEnabled
{
    /** 处理命令或请求的主流程。 */
    public function handle(Request $request, Closure $next, string ...$moduleKeys): Response
    {
        // 安装或迁移期间模块表尚不可用时降级放行，避免后台初始化流程被模块保护阻断。
        if (empty($moduleKeys) || !$this->moduleTableIsReady()) {
            return $next($request);
        }

        $expected = $this->normalizeKeys($moduleKeys);
        $modules = Module::query()->get(['name', 'enabled', 'config']);

        foreach ($modules as $module) {
            // 路由可传本地模块名、Registry ID 或 manifest ID，统一归一化后比较。
            $keys = $this->moduleKeys($module);
            if (empty(array_intersect_key($expected, $keys))) {
                continue;
            }

            if ((bool) $module->enabled) {
                return $next($request);
            }

            return response()->json([
                'code' => 40404,
                'msg' => '模块未启用或不可用。',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'code' => 40404,
            'msg' => '模块未启用或不可用。',
            'data' => null,
        ], 404);
    }

        /** 执行 moduleTableIsReady 方法对应的具体职责。 */
    private function moduleTableIsReady(): bool
    {
        try {
            return Schema::hasTable('modules');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 将输入值归一化为内部标准格式。
     * @param array<int, string> $keys
     * @return array<string, true>
     */
    private function normalizeKeys(array $keys): array
    {
        $normalized = [];

        foreach ($keys as $key) {
            $value = $this->normalizeKey($key);
            if ($value !== '') {
                $normalized[$value] = true;
            }
        }

        return $normalized;
    }

    /**
     * 执行 moduleKeys 方法对应的具体职责。
     * @return array<string, true>
     */
    private function moduleKeys(Module $module): array
    {
        $config = is_array($module->config) ? $module->config : [];

        return $this->normalizeKeys(array_filter([
            $module->name,
            $config['id'] ?? null,
            $config['registry_id'] ?? null,
            data_get($config, 'trix_manifest.id'),
        ], static fn ($value): bool => is_string($value) && trim($value) !== ''));
    }

        /** 将输入值归一化为内部标准格式。 */
    private function normalizeKey(string $value): string
    {
        // 忽略大小写、空格、点号和横线，让 ModuleMarket/module-market/module market 能匹配。
        return preg_replace('/[^a-z0-9]+/', '', strtolower($value)) ?? '';
    }
}
