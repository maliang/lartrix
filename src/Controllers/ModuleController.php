<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Lartrix\Modules\Manifest\ModuleManifestLoader;
use Lartrix\Schema\Pages\ModuleManagementSchema;
use Lartrix\Services\ModuleManagementService;
use Lartrix\Services\ModuleService;
use Nwidart\Modules\Facades\Module as ModuleFacade;

/** 仅负责本地模块列表、生命周期操作和模块 Logo。 */
class ModuleController extends Controller
{
    /** 注入本地模块应用服务与页面 Schema。 */
    public function __construct(
        private readonly ModuleService $modules,
        private readonly ModuleManagementService $management,
        private readonly ModuleManagementSchema $schema,
    ) {
    }

    /** 返回已安装模块页面或列表数据。 */
    public function index(Request $request): array
    {
        return $request->input('action_type') === 'installed_ui'
            ? $this->schema->installed()
            : success($this->management->modules());
    }

    /** 启用指定本地模块。 */
    public function enable(string $name): array
    {
        $this->assertExists($name);
        if (!$this->modules->enable($name)) {
            error(__t('module.enable_failed'), null, 40000);
        }

        return success(__t('module.enabled'));
    }

    /** 禁用指定本地模块。 */
    public function disable(string $name): array
    {
        $this->assertExists($name);
        if (!$this->modules->disable($name)) {
            error(__t('module.disable_failed'), null, 40000);
        }

        return success(__t('module.disabled'));
    }

    /** 执行指定本地模块的安装生命周期。 */
    public function install(string $name): array
    {
        $this->assertExists($name);
        if (!$this->modules->install($name)) {
            error(__t('module.install_failed'), null, 40000);
        }

        return success(__t('module.installed'));
    }

    /** 执行指定本地模块的卸载生命周期。 */
    public function uninstall(string $name): array
    {
        $this->assertExists($name);
        if (!$this->modules->uninstall($name)) {
            error(__t('module.uninstall_failed'), null, 40000);
        }

        return success(__t('module.uninstalled'));
    }

    /** 返回模块声明的本地 Logo，远程 Logo 使用安全重定向。 */
    public function logo(string $name)
    {
        $module = ModuleFacade::find($name);
        if (!$module) {
            abort(404, __t('module.not_found'));
        }

        try {
            $manifest = (new ModuleManifestLoader())->loadFromPath($module->getPath());
        } catch (\InvalidArgumentException) {
            $manifest = null;
        }
        $logo = trim((string) ($manifest?->logo() ?? ''));
        if ($logo === '') {
            abort(404, __t('module.logo_not_configured'));
        }
        if ($this->isRemoteLogoUrl($logo)) {
            return redirect()->away($logo, 302, ['Cache-Control' => 'public, max-age=86400']);
        }

        $moduleRoot = realpath($module->getPath());
        $fullPath = realpath($module->getPath() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logo));
        if (!is_string($moduleRoot) || !is_string($fullPath)
            || !str_starts_with($fullPath, $moduleRoot . DIRECTORY_SEPARATOR)
            || !is_file($fullPath)) {
            abort(404, __t('module.logo_not_found'));
        }

        $mimeTypes = [
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp', 'ico' => 'image/x-icon',
        ];
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!isset($mimeTypes[$extension])) {
            abort(404, __t('module.logo_not_found'));
        }

        return Response::file($fullPath, [
            'Content-Type' => $mimeTypes[$extension],
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** 确认本地模块存在。 */
    private function assertExists(string $name): void
    {
        if (!$this->modules->exists($name)) {
            error(__t('module.not_found'), null, 40102);
        }
    }

    /** 判断 Logo 是否为允许的 HTTP(S) 远程地址。 */
    private function isRemoteLogoUrl(string $logo): bool
    {
        return filter_var($logo, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($logo, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
