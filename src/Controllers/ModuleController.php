<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Lartrix\Modules\Project\ProjectInstallPlanStore;
use Lartrix\Modules\Registry\RegistryPackageDownloader;
use Lartrix\Modules\Registry\RegistryPackageStager;
use Lartrix\Modules\Registry\RegistryStagedManifestVerifier;
use Lartrix\Modules\Registry\RegistryVersionResolver;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Components\Business\DataTable;
use Lartrix\Schema\Components\Custom\Html;
use Lartrix\Schema\Components\Custom\SvgIcon;
use Lartrix\Schema\Components\NaiveUI\Avatar;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Card;
use Lartrix\Schema\Components\NaiveUI\Flex;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\Modal;
use Lartrix\Schema\Components\NaiveUI\Pagination;
use Lartrix\Schema\Components\NaiveUI\Popconfirm;
use Lartrix\Schema\Components\NaiveUI\Result;
use Lartrix\Schema\Components\NaiveUI\Select;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\TabPane;
use Lartrix\Schema\Components\NaiveUI\Tabs;
use Lartrix\Schema\Components\NaiveUI\Tag;
use Lartrix\Models\Module;
use Lartrix\Services\ModuleService;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Throwable;
use ZipArchive;

/** 提供模块管理、模块市场、发布与安装相关的后台接口和页面结构。 */
class ModuleController extends Controller
{
    /** 初始化当前对象及其依赖。 */
    public function __construct(
        protected ModuleService $moduleService
    ) {
    }

    /** 返回模块管理页面结构。 */
    public function index(Request $request): array
    {
        return match ($request->input('action_type', 'list')) {
            'market_ui' => $this->marketUi(),
            'installed_ui' => $this->installedUi(),
            default => $this->list(),
        };
    }

    /** 获取模块列表响应数据。 */
    protected function list(): array
    {
        return success($this->withPublishState($this->moduleService->getModules()));
    }

        /** 执行 marketModules 方法对应的具体职责。 */
    public function marketModules(Request $request): array
    {
        return success($this->fetchRegistryItems('/registry/modules', [
            'keyword' => $request->input('keyword', ''),
            'type' => $this->normalizeMarketType($request->input('type', 'all')),
            'language' => $request->input('language', 'php'),
            'framework' => $request->input('framework', 'laravel'),
            'page' => max(1, (int) $request->input('page', 1)),
            'page_size' => 16,
        ], 'module'));
    }

        /** 执行 marketProjects 方法对应的具体职责。 */
    public function marketProjects(Request $request): array
    {
        return success($this->fetchRegistryItems('/registry/projects', [
            'keyword' => $request->input('keyword', ''),
            'type' => $this->normalizeMarketType($request->input('type', 'all')),
            'language' => $request->input('language', 'php'),
            'framework' => $request->input('framework', 'laravel'),
            'page' => max(1, (int) $request->input('page', 1)),
            'page_size' => 16,
        ], 'project'));
    }

        /** 启用指定模块及其运行状态。 */
    public function enable(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error(__t('module.not_found'), null, 40102);
        }

        if (!$this->moduleService->enable($name)) {
            error(__t('module.enable_failed'), null, 40000);
        }

        return success(__t('module.enabled'));
    }

        /** 禁用指定模块及其运行状态。 */
    public function disable(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error(__t('module.not_found'), null, 40102);
        }

        if (!$this->moduleService->disable($name)) {
            error(__t('module.disable_failed'), null, 40000);
        }

        return success(__t('module.disabled'));
    }

        /** 执行模块或项目安装流程。 */
    public function install(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error(__t('module.not_found'), null, 40102);
        }

        if (!$this->moduleService->install($name)) {
            error(__t('module.install_failed'), null, 40000);
        }

        return success(__t('module.installed'));
    }

        /** 执行模块卸载及清理流程。 */
    public function uninstall(string $name): array
    {
        if (!$this->moduleService->exists($name)) {
            error(__t('module.not_found'), null, 40102);
        }

        if (!$this->moduleService->uninstall($name)) {
            error(__t('module.uninstall_failed'), null, 40000);
        }

        return success(__t('module.uninstalled'));
    }

        /** 发布当前模块、项目或资源。 */
    public function publishLocal(string $name): array
    {
        $module = ModuleFacade::find($name);
        if (!$module) {
            error(__t('module.not_found'), null, 40102);
        }

        $registry = $this->registryUrl();
        $authKey = $this->registryAuthKey();
        if ($registry === '' || $authKey === '') {
            error('请先配置模块市场地址和 TRIX_AUTH_KEY', null, 40000);
        }

        $modulePath = $module->getPath();
        $manifestPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
        if (!File::exists($manifestPath)) {
            error('模块缺少 module.json', null, 40000);
        }

        $manifest = json_decode((string) File::get($manifestPath), true);
        if (!is_array($manifest)) {
            error('module.json 不是合法 JSON', null, 40000);
        }

        $publisher = $this->registryPublisher();
        $authorError = $this->validateLocalAuthor($manifest, $publisher);
        if ($authorError !== null) {
            error($authorError, ['publisher' => $publisher['user'] ?? null], 403);
        }

        $versionError = $this->validatePublishVersion($manifest);
        if ($versionError !== null) {
            error($versionError, null, 40000);
        }

        $version = (string) ($manifest['version'] ?? 'dev');
        $zipPath = storage_path('app/registry-publish/' . $name . '-' . $version . '.zip');

        try {
            $this->zipDirectory($modulePath, $zipPath, basename($modulePath));
            $response = Http::timeout(60)
                ->withToken($authKey)
                ->attach('package', File::get($zipPath), basename($zipPath))
                ->post($registry . '/registry/publish/modules', [
                    'manifest' => json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
        } catch (Throwable $e) {
            error($e->getMessage(), null, 50000);
        }

        if (!$response->successful()) {
            error($response->json('msg') ?: $response->json('message') ?: '上传失败', $response->json(), 50000);
        }

        return success('已上传到 Registry，等待审核', $response->json('data'));
    }

        /** 发布当前模块、项目或资源。 */
    public function publishLocalProject(): array
    {
        $registry = $this->registryUrl();
        $authKey = $this->registryAuthKey();
        if ($registry === '' || $authKey === '') {
            error('请先配置模块市场地址和 TRIX_AUTH_KEY', null, 40000);
        }

        $manifestPath = base_path('trix-project.json');
        if (!File::exists($manifestPath)) {
            error('当前项目缺少 trix-project.json，请先执行 lartrix:project-make。', null, 40000);
        }

        $manifest = json_decode((string) File::get($manifestPath), true);
        if (!is_array($manifest)) {
            error('trix-project.json 不是合法 JSON', null, 40000);
        }

        $manifestError = $this->validateProjectManifest($manifest);
        if ($manifestError !== null) {
            error($manifestError, null, 40000);
        }

        $publisher = $this->registryPublisher();
        $authorError = $this->validateLocalAuthor($manifest, $publisher);
        if ($authorError !== null) {
            error(str_replace('module.json', 'trix-project.json', $authorError), ['publisher' => $publisher['user'] ?? null], 403);
        }

        $versionError = $this->validateProjectPublishVersion($manifest);
        if ($versionError !== null) {
            error($versionError, null, 40000);
        }

        $projectId = (string) ($manifest['id'] ?? 'local.project');
        $version = (string) ($manifest['version'] ?? 'dev');
        $zipPath = storage_path('app/registry-publish/projects/' . $this->safePackageName($projectId . '-' . $version) . '.zip');

        try {
            $this->zipProject(base_path(), $zipPath, basename(base_path()));
            $response = Http::timeout(60)
                ->withToken($authKey)
                ->attach('package', File::get($zipPath), basename($zipPath))
                ->post($registry . '/registry/publish/projects', [
                    'manifest' => json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
        } catch (Throwable $e) {
            error($e->getMessage(), null, 50000);
        }

        if (!$response->successful()) {
            error($response->json('msg') ?: $response->json('message') ?: '项目上传失败', $response->json(), 50000);
        }

        return success('当前项目已上传到 Registry，等待审核', $response->json('data'));
    }

        /** 执行模块或项目安装流程。 */
    public function installMarketModule(string $id): array
    {
        $result = $this->stageRegistryModule($id);

        if (!($result['staged'] ?? false)) {
            error((string) ($result['message'] ?? '市场模块安装准备失败'), $result, 40000);
        }

        return success('模块包已下载并暂存，请按返回命令完成本地安装', $result);
    }

        /** 执行模块或项目安装流程。 */
    public function installMarketProject(string $id): array
    {
        $registry = $this->registryUrl();
        if ($registry === '') {
            error('Please configure module registry URL.', null, 40000);
        }

        $versions = $this->registryRequest()->get($registry . '/registry/projects/' . rawurlencode($id) . '/versions', [
            'page_size' => 1,
            'language' => 'php',
            'framework' => 'laravel',
        ]);

        if (!$versions->successful()) {
            error($versions->json('msg') ?: $versions->json('message') ?: 'Project version query failed.', $versions->json(), 40000);
        }

        $version = data_get($versions->json(), 'data.items.0.version') ?: data_get($versions->json(), 'data.version');
        if (!is_string($version) || $version === '') {
            error('Project has no installable version.', $versions->json(), 40000);
        }

        $response = $this->registryRequest()
            ->get($registry . '/registry/projects/' . rawurlencode($id) . '/versions/' . rawurlencode($version) . '/install-plan', [
                'language' => 'php',
                'framework' => 'laravel',
            ]);

        if (!$response->successful()) {
            error($response->json('msg') ?: $response->json('message') ?: 'Project install plan query failed.', $response->json(), 40000);
        }

        $plan = $response->json('data');
        if (is_array($plan)) {
            $plan['local_plan_path'] = $this->saveProjectInstallPlan($id, $version, $plan);
        }

        return success('Project install plan has been saved.', $plan);
    }
    /** 获取模块 Logo 地址。 */
    public function logo(string $name)
    {
        $module = ModuleFacade::find($name);
        if (!$module) {
            abort(404, __t('module.not_found'));
        }

        $logoFile = $module->json()->get('logo', '');
        if (empty($logoFile)) {
            abort(404, __t('module.logo_not_configured'));
        }

        if ($this->isRemoteLogoUrl($logoFile)) {
            return redirect()->away($logoFile, 302, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $fullPath = $module->getPath() . '/' . $logoFile;
        if (!file_exists($fullPath)) {
            abort(404, __t('module.logo_not_found'));
        }

        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
        ];
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        return Response::file($fullPath, [
            'Content-Type' => $mimeTypes[$extension] ?? 'application/octet-stream',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

        /** 判断当前业务条件是否成立。 */
    protected function isRemoteLogoUrl(string $logoFile): bool
    {
        if (!filter_var($logoFile, FILTER_VALIDATE_URL)) {
            return false;
        }

        return in_array(strtolower((string) parse_url($logoFile, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /**
     * 保存当前业务数据。
     * @param array<string, mixed> $plan
     */
    protected function saveProjectInstallPlan(string $projectId, string $version, array $plan): string
    {
        $paths = (new ProjectInstallPlanStore())->save($projectId, $version, $plan);

        return $paths['install_plan'];
    }

    /**
     * 执行 withPublishState 方法对应的具体职责。
     * @param array<int, array<string, mixed>> $modules
     * @return array<int, array<string, mixed>>
     */
    protected function withPublishState(array $modules): array
    {
        $publisher = $this->registryPublisherOrNull();

        return array_map(function (array $module) use ($publisher): array {
            $config = is_array($module['config'] ?? null) ? $module['config'] : [];
            $manifest = is_array($config['trix_manifest'] ?? null) ? $config['trix_manifest'] : $config;
            $localVersion = (string) ($manifest['version'] ?? $module['version'] ?? '');
            $registryId = $this->manifestRegistryId($manifest, $module);
            $authorOk = $publisher !== null && $this->validateLocalAuthor($manifest, $publisher) === null;
            $remoteVersion = $registryId !== '' ? $this->registryLatestModuleVersion($registryId) : null;
            $versionIncreased = $remoteVersion === null || ($localVersion !== '' && version_compare($localVersion, $remoteVersion, '>'));

            $module['registry_id'] = $registryId;
            $module['remote_version'] = $remoteVersion;
            $module['can_publish'] = $authorOk && $versionIncreased;
            $module['publish_status'] = $this->publishStatus($publisher, $authorOk, $localVersion, $remoteVersion, $versionIncreased);

            return $module;
        }, $modules);
    }

        /** 发布当前模块、项目或资源。 */
    protected function publishStatus(?array $publisher, bool $authorOk, string $localVersion, ?string $remoteVersion, bool $versionIncreased): string
    {
        if ($publisher === null) {
            return 'auth_key_missing';
        }

        if (!$authorOk) {
            return 'author_mismatch';
        }

        if ($localVersion === '') {
            return 'local_version_missing';
        }

        if (!$versionIncreased) {
            return 'version_not_increased';
        }

        return $remoteVersion === null ? 'new_release' : 'version_increased';
    }

        /** 将已校验的发布包展开到暂存目录。 */
    protected function stageRegistryModule(string $moduleId): array
    {
        $registry = $this->registryUrl();
        if ($registry === '') {
            return ['staged' => false, 'message' => '请先配置模块市场地址'];
        }

        $response = $this->registryRequest()->get($registry . '/registry/modules/' . rawurlencode($moduleId) . '/versions', [
            'page_size' => 1,
            'language' => 'php',
            'framework' => 'laravel',
        ]);

        if (!$response->successful()) {
            return ['staged' => false, 'message' => '市场模块查询失败', 'status' => $response->status(), 'response' => $response->json()];
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return ['staged' => false, 'message' => '市场模块返回格式错误'];
        }

        $resolved = (new RegistryVersionResolver('php', 'laravel'))->resolveLatest($payload);
        if (!($resolved['installable'] ?? false)) {
            return ['staged' => false, 'message' => (string) ($resolved['message'] ?? '当前模块没有可安装的 PHP/Laravel adapter')];
        }

        $adapter = is_array($resolved['adapter'] ?? null) ? $resolved['adapter'] : [];
        $version = is_array($resolved['version'] ?? null) ? $resolved['version'] : [];
        $versionNumber = (string) ($version['version'] ?? 'latest');
        $fetcher = fn (string $url): ?string => $this->registryRequest()->get($url)->body();

        $download = (new RegistryPackageDownloader(signatureKey: (string) config('lartrix.module_registry.signature_key', ''), fetcher: $fetcher))
            ->download($adapter, $moduleId, $versionNumber);

        if (!($download['downloaded'] ?? false)) {
            return ['staged' => false, 'message' => (string) ($download['message'] ?? '模块包下载失败'), 'download' => $download];
        }

        $stage = (new RegistryPackageStager())->stage((string) $download['path'], $moduleId, $versionNumber);
        if (!($stage['staged'] ?? false)) {
            return ['staged' => false, 'message' => (string) ($stage['message'] ?? '模块包暂存失败'), 'download' => $download, 'stage' => $stage];
        }

        $verify = (new RegistryStagedManifestVerifier('php', 'laravel'))
            ->verify((string) $stage['path'], (string) $stage['manifest'], $moduleId, $versionNumber);
        if (!($verify['ok'] ?? false)) {
            return ['staged' => false, 'message' => (string) ($verify['message'] ?? '模块包 manifest 校验失败'), 'download' => $download, 'stage' => $stage, 'verify' => $verify];
        }

        return [
            'staged' => true,
            'module' => $moduleId,
            'version' => $versionNumber,
            'package_path' => $download['path'],
            'stage_path' => $stage['path'],
            'manifest' => $stage['manifest'],
            'commands' => [
                'php artisan lartrix:module-install ' . $moduleId . ' --from-stage="' . $stage['path'] . '" --manifest="' . $stage['manifest'] . '" --version="' . $versionNumber . '" --target-dir="Modules/' . $this->moduleDirectoryName($moduleId) . '"',
                'php artisan module:enable ' . $this->moduleDirectoryName($moduleId),
            ],
        ];
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registryUrl(): string
    {
        return rtrim((string) config('lartrix.module_registry.url', ''), '/');
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registryAuthKey(): string
    {
        return trim((string) config('lartrix.module_registry.auth_key', ''));
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registryRequest(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::acceptJson()->timeout(60);
        $authKey = $this->registryAuthKey();

        return $authKey === '' ? $request : $request->withToken($authKey);
    }

    /**
     * 处理 Registry 地址、认证或请求。
     * @return array<string, mixed>
     */
    protected function registryPublisher(): array
    {
        $registry = $this->registryUrl();
        if ($registry === '') {
            error('请先配置模块市场地址', null, 40000);
        }

        $authKey = $this->registryAuthKey();
        if ($authKey === '') {
            error('请先配置 TRIX_AUTH_KEY', null, 40000);
        }

        $response = $this->registryRequest()->get($registry . '/registry/auth/me');
        if (!$response->successful()) {
            error($response->json('msg') ?: $response->json('message') ?: 'Auth Key 无效或没有发布权限', $response->json(), 403);
        }

        $payload = $response->json('data');
        if (!is_array($payload) || !is_array($payload['user'] ?? null)) {
            error('模块市场用户信息返回格式错误', $response->json(), 40000);
        }

        return $payload;
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registryPublisherOrNull(): ?array
    {
        try {
            return $this->registryPublisher();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $publisher
     */
    protected function validateLocalAuthor(array $manifest, array $publisher): ?string
    {
        $author = $this->normalizeAuthor($manifest['author'] ?? null);
        if ($author === '') {
            return 'module.json 必须填写 author，且 author 必须匹配当前 Auth Key 用户。';
        }

        $user = is_array($publisher['user'] ?? null) ? $publisher['user'] : [];
        $allowed = array_filter([
            $this->normalizeAuthor($user['name'] ?? null),
            $this->normalizeAuthor($user['email'] ?? null),
        ]);

        return in_array($author, $allowed, true) ? null : '只能上传自己作为作者的模块。';
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $manifest
     */
    protected function validatePublishVersion(array $manifest): ?string
    {
        $registryId = $this->manifestRegistryId($manifest, []);
        $localVersion = is_string($manifest['version'] ?? null) ? trim($manifest['version']) : '';

        if ($registryId === '' || $localVersion === '') {
            return 'module.json 必须填写 id 和 version。';
        }

        $remoteVersion = $this->registryLatestModuleVersion($registryId);
        if ($remoteVersion !== null && !version_compare($localVersion, $remoteVersion, '>')) {
            return "本地版本 {$localVersion} 未高于市场最新版本 {$remoteVersion}，请先提升模块版本。";
        }

        return null;
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $manifest
     */
    protected function validateProjectManifest(array $manifest): ?string
    {
        foreach (['schema_version', 'id', 'name', 'version', 'author'] as $field) {
            if (!is_string($manifest[$field] ?? null) || trim($manifest[$field]) === '') {
                return "trix-project.json 必须填写 {$field}。";
            }
        }

        return $manifest['schema_version'] === 'trix.project.v1'
            ? null
            : 'trix-project.json 的 schema_version 必须是 trix.project.v1。';
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $manifest
     */
    protected function validateProjectPublishVersion(array $manifest): ?string
    {
        $registryId = $this->manifestRegistryId($manifest, []);
        $localVersion = is_string($manifest['version'] ?? null) ? trim($manifest['version']) : '';

        if ($registryId === '' || $localVersion === '') {
            return 'trix-project.json 必须填写 id 和 version。';
        }

        $remoteVersion = $this->registryLatestProjectVersion($registryId);
        if ($remoteVersion !== null && !version_compare($localVersion, $remoteVersion, '>')) {
            return "本地项目版本 {$localVersion} 未高于市场最新版本 {$remoteVersion}，请先提升项目版本。";
        }

        return null;
    }

    /**
     * 执行 manifestRegistryId 方法对应的具体职责。
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $module
     */
    protected function manifestRegistryId(array $manifest, array $module): string
    {
        foreach ([
            $manifest['id'] ?? null,
            $manifest['registry_id'] ?? null,
            data_get($manifest, 'trix_manifest.id'),
            $module['registry_id'] ?? null,
            $module['name'] ?? null,
        ] as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registryLatestModuleVersion(string $registryId): ?string
    {
        $registry = $this->registryUrl();
        if ($registry === '') {
            return null;
        }

        try {
            $response = $this->registryRequest()->get($registry . '/registry/modules/' . rawurlencode($registryId) . '/versions', [
                'page_size' => 1,
                'language' => 'php',
                'framework' => 'laravel',
            ]);
        } catch (\Throwable) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $version = data_get($response->json(), 'data.items.0.version') ?: data_get($response->json(), 'data.version');

        return is_string($version) && trim($version) !== '' ? trim($version) : null;
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registryLatestProjectVersion(string $registryId): ?string
    {
        $registry = $this->registryUrl();
        if ($registry === '') {
            return null;
        }

        try {
            $response = $this->registryRequest()->get($registry . '/registry/projects/' . rawurlencode($registryId) . '/versions', [
                'page_size' => 1,
                'language' => 'php',
                'framework' => 'laravel',
            ]);
        } catch (\Throwable) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $version = data_get($response->json(), 'data.items.0.version') ?: data_get($response->json(), 'data.version');

        return is_string($version) && trim($version) !== '' ? trim($version) : null;
    }

        /** 将输入值归一化为内部标准格式。 */
    protected function normalizeAuthor(mixed $value): string
    {
        return is_string($value) ? mb_strtolower(trim($value)) : '';
    }

        /** 执行 moduleDirectoryName 方法对应的具体职责。 */
    protected function moduleDirectoryName(string $moduleId): string
    {
        return str_replace(' ', '', ucwords(str_replace(['.', '-', '_'], ' ', $moduleId)));
    }

    /** 将模块目录递归打包为 ZIP 文件。 */
    protected function zipDirectory(string $sourceDir, string $zipPath, string $rootName): void
    {
        File::ensureDirectoryExists(dirname($zipPath));

        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('无法创建模块压缩包');
        }

        foreach (File::allFiles($sourceDir) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if (preg_match('#^(vendor|node_modules|\.git|\.idea|\.vscode)(/|$)#', $relative)) {
                continue;
            }

            $zip->addFile($file->getRealPath(), $rootName . '/' . $relative);
        }

        $zip->close();
    }

    /** 按项目发布规则打包项目清单与覆盖配置。 */
    protected function zipProject(string $sourceDir, string $zipPath, string $rootName): void
    {
        File::ensureDirectoryExists(dirname($zipPath));

        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('无法创建项目压缩包');
        }

        foreach (File::allFiles($sourceDir) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if (preg_match('#^(vendor|node_modules|\.git|\.idea|\.vscode|storage/(logs|framework/cache|framework/sessions|framework/views)|bootstrap/cache)(/|$)#', $relative)) {
                continue;
            }

            $zip->addFile($file->getRealPath(), $rootName . '/' . $relative);
        }

        $zip->close();
    }

        /** 生成可安全用于文件系统的名称。 */
    protected function safePackageName(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: 'package';
    }

        /** 从远端服务获取并解析数据。 */
    protected function fetchRegistryItems(string $endpoint, array $query, string $type): array
    {
        $registry = rtrim((string) config('lartrix.module_registry.url', ''), '/');
        if ($registry === '') {
            return $this->emptyRegistryPage($query);
        }

        $request = Http::acceptJson()->timeout(15);
        $authKey = trim((string) config('lartrix.module_registry.auth_key', ''));
        if ($authKey !== '') {
            $request = $request->withToken($authKey);
        }

        $response = $request->get($registry . $endpoint, array_filter($query, fn ($value) => $value !== '' && $value !== null));
        if (!$response->successful()) {
            return $this->emptyRegistryPage($query);
        }

        $payload = $response->json();
        $items = data_get($payload, 'data.items', []);
        if (!is_array($items)) {
            return $this->emptyRegistryPage($query);
        }

        $installedModuleIds = $type === 'module' ? $this->installedModuleIds() : [];

        $formatted = collect($items)
            ->map(fn (array $item): array => $type === 'project' ? $this->formatRegistryProject($item) : $this->formatRegistryModule($item, $installedModuleIds))
            ->values()
            ->all();

        return [
            'items' => $formatted,
            'page' => (int) data_get($payload, 'data.page', $query['page'] ?? 1),
            'page_size' => (int) data_get($payload, 'data.page_size', $query['page_size'] ?? count($formatted)),
            'total' => (int) data_get($payload, 'data.total', count($formatted)),
        ];
    }

    /** 构造空的模块市场分页结果。 */
    protected function emptyRegistryPage(array $query): array
    {
        return [
            'items' => [],
            'page' => (int) ($query['page'] ?? 1),
            'page_size' => (int) ($query['page_size'] ?? 16),
            'total' => 0,
        ];
    }

    /**
     * 将数据格式化为接口或页面需要的结构。
     * @param array<string, true> $installedModuleIds
     */
    protected function formatRegistryModule(array $item, array $installedModuleIds = []): array
    {
        $latest = is_array($item['latest_version'] ?? null) ? $item['latest_version'] : [];
        $latestVersion = is_string($item['latest_version'] ?? null) ? $item['latest_version'] : null;
        $id = (string) ($item['id'] ?? $item['registry_id'] ?? $item['name'] ?? '');
        $name = (string) ($item['name'] ?? '');
        $installed = $this->isRegistryModuleInstalled($id, $name, $installedModuleIds);

        return [
            'id' => $id,
            'title' => $item['title'] ?? $item['name'] ?? $item['id'] ?? '',
            'summary' => $item['summary'] ?? $item['description'] ?? '',
            'version' => $latest['version'] ?? $latestVersion ?? $item['version'] ?? '-',
            'type' => $moduleType = (string) ($item['module_type'] ?? $item['type'] ?? '-'),
            'type_label' => $this->marketTypeLabel($moduleType, 'module'),
            'logo' => $item['logo'] ?? $item['icon'] ?? null,
            'thumbnail' => $item['thumbnail'] ?? null,
            'author' => $item['author'] ?? '-',
            'author_url' => $item['author_url'] ?? null,
            'downloads' => $item['downloads_count'] ?? $item['downloads'] ?? 0,
            'license' => $item['license'] ?? '-',
            'installed' => $installed,
            'install_status' => $installed ? 'installed' : 'available',
        ];
    }

    /**
     * 执行模块或项目安装流程。
     * @return array<string, true>
     */
    protected function installedModuleIds(): array
    {
        $ids = [];
        Module::query()->get(['name', 'config'])->each(function (Module $module) use (&$ids): void {
            $this->rememberModuleId($ids, $module->name);

            $config = is_array($module->config) ? $module->config : [];
            $this->rememberModuleId($ids, $config['id'] ?? null);
            $this->rememberModuleId($ids, $config['registry_id'] ?? null);
            $this->rememberModuleId($ids, data_get($config, 'trix_manifest.id'));
        });

        return $ids;
    }

    /**
     * 执行 rememberModuleId 方法对应的具体职责。
     * @param array<string, true> $ids
     */
    protected function rememberModuleId(array &$ids, mixed $value): void
    {
        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $ids[strtolower(trim($value))] = true;
    }

    /**
     * 判断当前业务条件是否成立。
     * @param array<string, true> $installedModuleIds
     */
    protected function isRegistryModuleInstalled(string $id, string $name, array $installedModuleIds): bool
    {
        foreach ([$id, $name] as $candidate) {
            $candidate = strtolower(trim($candidate));
            if ($candidate !== '' && isset($installedModuleIds[$candidate])) {
                return true;
            }
        }

        return false;
    }

        /** 将数据格式化为接口或页面需要的结构。 */
    protected function formatRegistryProject(array $item): array
    {
        $latest = is_array($item['latest_version'] ?? null) ? $item['latest_version'] : [];
        $latestVersion = is_string($item['latest_version'] ?? null) ? $item['latest_version'] : null;

        return [
            'id' => $item['id'] ?? $item['registry_id'] ?? $item['name'] ?? '',
            'title' => $item['title'] ?? $item['name'] ?? $item['id'] ?? '',
            'summary' => $item['summary'] ?? $item['description'] ?? '',
            'version' => $latest['version'] ?? $latestVersion ?? $item['version'] ?? '-',
            'type' => $projectType = (string) ($item['project_type'] ?? $item['type'] ?? '-'),
            'type_label' => $this->marketTypeLabel($projectType, 'project'),
            'logo' => $item['logo'] ?? $item['icon'] ?? null,
            'thumbnail' => $item['thumbnail'] ?? null,
            'author' => $item['author'] ?? '-',
            'author_url' => $item['author_url'] ?? null,
            'license' => $item['license'] ?? '-',
        ];
    }

        /** 执行 moduleTypeOptions 方法对应的具体职责。 */
    protected function moduleTypeOptions(): array
    {
        return [
            ['label' => '全部', 'value' => 'all'],
            ['label' => '基础能力', 'value' => 'core'],
            ['label' => '业务模块', 'value' => 'business'],
            ['label' => '外部集成', 'value' => 'integration'],
            ['label' => '界面组件', 'value' => 'ui'],
            ['label' => '开发工具', 'value' => 'tooling'],
        ];
    }

    /** 返回项目分类筛选选项。 */
    protected function projectTypeOptions(): array
    {
        return [
            ['label' => '全部', 'value' => 'all'],
            ['label' => '起步模板', 'value' => 'starter'],
            ['label' => '行业方案', 'value' => 'solution'],
            ['label' => '演示项目', 'value' => 'demo'],
            ['label' => '结构模板', 'value' => 'template'],
            ['label' => '企业工程', 'value' => 'enterprise'],
        ];
    }

        /** 执行 marketTypeLabel 方法对应的具体职责。 */
    protected function marketTypeLabel(string $type, string $kind): string
    {
        if ($type === '' || $type === '-') {
            return '-';
        }

        $options = $kind === 'project' ? $this->projectTypeOptions() : $this->moduleTypeOptions();
        foreach ($options as $option) {
            if (($option['value'] ?? null) === $type) {
                return (string) ($option['label'] ?? $type);
            }
        }

        return $type;
    }

        /** 将输入值归一化为内部标准格式。 */
    protected function normalizeMarketType(mixed $type): string
    {
        $type = is_string($type) ? trim($type) : '';

        return $type === 'all' ? '' : $type;
    }

        /** 执行 marketUi 方法对应的具体职责。 */
    protected function marketUi(): array
    {
        $schema = Card::make()
            ->props(['title' => __t('title.module_market')])
            ->children([
                Result::make()
                    ->props([
                        'status' => 'info',
                        'title' => __t('title.coming_soon'),
                        'description' => __t('title.coming_soon_desc'),
                    ])
                    ->slot('icon', [
                        SvgIcon::make('carbon:store')->props(['class' => 'text-6xl text-primary']),
                    ]),
            ]);

        return success($schema->toArray());
    }

        /** 执行模块或项目安装流程。 */
    protected function installedUi(): array
    {
        $routePrefix = '/' . config('lartrix.route_prefix', 'api/admin');

        $schema = Card::make()
            ->props(['title' => __t('title.installed_modules')])
            ->data([
                'modules' => [],
                'loading' => false,
                'routePrefix' => $routePrefix,
                'marketVisible' => false,
                'marketActiveTab' => 'modules',
                'marketModuleKeyword' => '',
                'marketModuleType' => 'all',
                'marketProjectKeyword' => '',
                'marketProjectType' => 'all',
                'marketModuleTypeOptions' => $this->moduleTypeOptions(),
                'marketProjectTypeOptions' => $this->projectTypeOptions(),
                'marketModules' => [],
                'marketProjects' => [],
                'marketModulePage' => 1,
                'marketProjectPage' => 1,
                'marketModulePageSize' => 16,
                'marketProjectPageSize' => 16,
                'marketModuleTotal' => 0,
                'marketProjectTotal' => 0,
                'marketModuleLoading' => false,
                'marketProjectLoading' => false,
                'marketDetailVisible' => false,
                'marketDetailKind' => 'module',
                'marketDetailItem' => null,
                'marketRegistryUrl' => rtrim((string) config('lartrix.module_registry.url', ''), '/'),
            ])
            ->methods($this->installedUiMethods())
            ->onMounted(CallAction::make('loadData'))
            ->children([
                Space::make()
                    ->props(['justify' => 'end', 'style' => 'margin-bottom: 16px'])
                    ->children([
                        Button::make()
                            ->type('info')
                            ->on('click', ['call' => 'handlePublishProject'])
                            ->text('上传当前项目'),
                        Button::make()
                            ->type('primary')
                            ->on('click', ['call' => 'openModuleMarket'])
                            ->text('模块市场'),
                ]),
                $this->installedModulesTable(),
                $this->moduleMarketModal(),
                $this->marketDetailModal(),
            ]);

        return success($schema->toArray());
    }

        /** 执行模块或项目安装流程。 */
    protected function installedUiMethods(): array
    {
        return [
            'openModuleMarket' => [
                SetAction::make('marketVisible', true),
                SetAction::make('marketModulePage', 1),
                SetAction::make('marketProjectPage', 1),
                CallAction::make('loadMarketModules'),
                CallAction::make('loadMarketProjects'),
            ],
            'loadMarketModules' => [
                SetAction::make('marketModuleLoading', true),
                FetchAction::make('/modules/market/modules')
                    ->get()
                    ->params(['keyword' => '{{ marketModuleKeyword }}', 'type' => '{{ marketModuleType }}', 'language' => 'php', 'framework' => 'laravel', 'page' => '{{ marketModulePage }}', 'page_size' => '{{ marketModulePageSize }}'])
                    ->then([
                        SetAction::make('marketModules', '{{ $response.data.items || [] }}'),
                        SetAction::make('marketModuleTotal', '{{ $response.data.total || 0 }}'),
                        SetAction::make('marketModulePage', '{{ $response.data.page || marketModulePage }}'),
                    ])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "模块市场加载失败" }}'])])
                    ->finally([SetAction::make('marketModuleLoading', false)]),
            ],
            'loadMarketProjects' => [
                SetAction::make('marketProjectLoading', true),
                FetchAction::make('/modules/market/projects')
                    ->get()
                    ->params(['keyword' => '{{ marketProjectKeyword }}', 'type' => '{{ marketProjectType }}', 'language' => 'php', 'framework' => 'laravel', 'page' => '{{ marketProjectPage }}', 'page_size' => '{{ marketProjectPageSize }}'])
                    ->then([
                        SetAction::make('marketProjects', '{{ $response.data.items || [] }}'),
                        SetAction::make('marketProjectTotal', '{{ $response.data.total || 0 }}'),
                        SetAction::make('marketProjectPage', '{{ $response.data.page || marketProjectPage }}'),
                    ])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "项目市场加载失败" }}'])])
                    ->finally([SetAction::make('marketProjectLoading', false)]),
            ],
            'loadData' => [
                SetAction::make('loading', true),
                FetchAction::make('/modules')
                    ->get()
                    ->then([SetAction::make('modules', '{{ $response.data || [] }}')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('crud.load_failed') . '" }}'])])
                    ->finally([SetAction::make('loading', false)]),
            ],
            'searchMarketModules' => [
                SetAction::make('marketModulePage', 1),
                CallAction::make('loadMarketModules'),
            ],
            'searchMarketProjects' => [
                SetAction::make('marketProjectPage', 1),
                CallAction::make('loadMarketProjects'),
            ],
            'handleMarketModulePageChange' => [
                SetAction::make('marketModulePage', '{{ $event }}'),
                CallAction::make('loadMarketModules'),
            ],
            'handleMarketProjectPageChange' => [
                SetAction::make('marketProjectPage', '{{ $event }}'),
                CallAction::make('loadMarketProjects'),
            ],
            'showMarketModuleDetail' => [
                SetAction::make('marketDetailKind', 'module'),
                SetAction::make('marketDetailItem', '{{ $event }}'),
                SetAction::make('marketDetailVisible', true),
            ],
            'showMarketProjectDetail' => [
                SetAction::make('marketDetailKind', 'project'),
                SetAction::make('marketDetailItem', '{{ $event }}'),
                SetAction::make('marketDetailVisible', true),
            ],
            'handleEnable' => [
                FetchAction::make('/modules/{{ $event }}/enable')
                    ->put()
                    ->then([CallAction::make('$message.success', [__t('module.enabled')]), CallAction::make('loadData')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.enable_failed') . '" }}'])]),
            ],
            'handleDisable' => [
                FetchAction::make('/modules/{{ $event }}/disable')
                    ->put()
                    ->then([CallAction::make('$message.success', [__t('module.disabled')]), CallAction::make('loadData')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.disable_failed') . '" }}'])]),
            ],
            'handleInstall' => [
                FetchAction::make('/modules/{{ $event }}/install')
                    ->put()
                    ->then([CallAction::make('$message.success', [__t('module.installed')]), CallAction::make('loadData')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.install_failed') . '" }}'])]),
            ],
            'handleUninstall' => [
                FetchAction::make('/modules/{{ $event }}/uninstall')
                    ->put()
                    ->then([CallAction::make('$message.success', [__t('module.uninstalled')]), CallAction::make('loadData')])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "' . __t('module.uninstall_failed') . '" }}'])]),
            ],
            'handlePublishModule' => [
                FetchAction::make('/modules/{{ $event }}/publish')
                    ->post()
                    ->then([CallAction::make('$message.success', ['{{ $response.msg || "已上传到模块市场，等待审核" }}'])])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "上传失败，请检查 TRIX_AUTH_KEY 和模块市场配置" }}'])]),
            ],
            'handlePublishProject' => [
                FetchAction::make('/modules/projects/publish')
                    ->post()
                    ->then([CallAction::make('$message.success', ['{{ $response.msg || "当前项目已上传到模块市场，等待审核" }}'])])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "项目上传失败，请检查 trix-project.json、TRIX_AUTH_KEY 和模块市场配置" }}'])]),
            ],
            'handleInstallMarketModule' => [
                FetchAction::make('/modules/market/modules/{{ marketDetailItem.id }}/install')
                    ->post()
                    ->then([
                        CallAction::make('$message.success', ['{{ $response.msg || "模块包已下载并暂存" }}']),
                        SetAction::make('marketDetailVisible', false),
                        CallAction::make('loadMarketModules'),
                    ])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "市场模块安装准备失败" }}'])]),
            ],
            'handleInstallMarketProject' => [
                FetchAction::make('/modules/market/projects/{{ marketDetailItem.id }}/install')
                    ->post()
                    ->then([
                        CallAction::make('$message.success', ['{{ $response.msg || "已获取项目安装计划" }}']),
                        SetAction::make('marketDetailVisible', false),
                    ])
                    ->catch([CallAction::make('$message.error', ['{{ $error.message || "项目安装计划获取失败" }}'])]),
            ],
        ];
    }

        /** 执行模块或项目安装流程。 */
    protected function installedModulesTable(): DataTable
    {
        $enabledLabel = json_encode('已启用', JSON_UNESCAPED_UNICODE);
        $disabledLabel = json_encode('已禁用', JSON_UNESCAPED_UNICODE);

        return DataTable::make()
            ->dataSource('modules')
            ->loading('loading')
            ->rowKey('name')
            ->columns([
                ['key' => 'logo', 'title' => __t('column.logo'), 'width' => 60, 'slot' => [
                    Avatar::make()
                        ->if('slotData.row.logo')
                        ->props(['src' => '{{ slotData.row.logo }}', 'size' => 32, 'objectFit' => 'contain']),
                    SvgIcon::make('carbon:cube')
                        ->if('!slotData.row.logo')
                        ->props(['class' => 'text-2xl text-primary']),
                ]],
                ['key' => 'name', 'title' => __t('column.name'), 'width' => 150],
                ['key' => 'version', 'title' => __t('column.version'), 'width' => 80],
                ['key' => 'description', 'title' => __t('column.description'), 'ellipsis' => true],
                ['key' => 'author', 'title' => __t('column.author'), 'width' => 100],
                ['key' => 'website', 'title' => __t('column.website'), 'width' => 120, 'ellipsis' => true, 'slot' => [
                    Button::make()
                        ->if('slotData.row.website')
                        ->size('small')
                        ->props(['text' => true, 'type' => 'primary', 'tag' => 'a', 'href' => '{{ slotData.row.website }}', 'target' => '_blank'])
                        ->children([__t('button.visit')]),
                ]],
                ['key' => 'enabled', 'title' => __t('column.status'), 'width' => 80, 'slot' => [
                    Tag::make()
                        ->props(['type' => "{{ slotData.row.enabled ? 'success' : 'default' }}", 'size' => 'small'])
                        ->children(["{{ slotData.row.enabled ? {$enabledLabel} : {$disabledLabel} }}"]),
                ]],
                ['key' => 'actions', 'title' => __t('column.actions'), 'width' => 220, 'slot' => [
                    Space::make()->children([
                        Button::make()
                            ->if('slotData.row.can_publish')
                            ->size('small')
                            ->type('primary')
                            ->props(['text' => true])
                            ->on('click', ['call' => 'handlePublishModule', 'args' => ['{{ slotData.row.name }}']])
                            ->text('上传'),
                        Button::make()
                            ->if('!slotData.row.enabled')
                            ->size('small')
                            ->type('primary')
                            ->props(['text' => true])
                            ->on('click', ['call' => 'handleEnable', 'args' => ['{{ slotData.row.name }}']])
                            ->text(__t('tag.enabled')),
                        Button::make()
                            ->if('slotData.row.enabled')
                            ->size('small')
                            ->type('warning')
                            ->props(['text' => true])
                            ->on('click', ['call' => 'handleDisable', 'args' => ['{{ slotData.row.name }}']])
                            ->text(__t('tag.disabled')),
                        Popconfirm::make()
                            ->on('positive-click', ['call' => 'handleUninstall', 'args' => ['{{ slotData.row.name }}']])
                            ->slot('trigger', [
                                Button::make()
                                    ->size('small')
                                    ->type('error')
                                    ->props(['text' => true])
                                    ->text(__t('button.uninstall')),
                            ])
                            ->children([__t('confirm.disable')]),
                    ]),
                ]],
            ]);
    }

        /** 执行 moduleMarketModal 方法对应的具体职责。 */
    protected function moduleMarketModal(): Modal
    {
        return Modal::make()
            ->show('marketVisible')
            ->title('模块市场')
            ->preset('card')
            ->style(['width' => '1080px'])
            ->props(['content-style' => ['height' => '682px', 'padding' => '16px 20px 12px', 'overflow' => 'hidden', 'boxSizing' => 'border-box']])
            ->on('update:show', SetAction::make('marketVisible', '{{ $event }}'))
            ->children([
                Tabs::make()
                    ->type('line')
                    ->model(['value' => 'marketActiveTab'])
                    ->props(['style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column']])
                    ->children([
                        TabPane::make()->name('modules')->tab('模块')->children($this->marketModulesPane()),
                        TabPane::make()->name('projects')->tab('项目')->children($this->marketProjectsPane()),
                    ]),
            ]);
    }

        /** 执行 marketModulesPane 方法对应的具体职责。 */
    protected function marketModulesPane(): array
    {
        return $this->marketPane('marketModules', 'marketModuleType', 'marketModuleKeyword', 'marketModuleTypeOptions', 'searchMarketModules', 'module', 'marketModulePage', 'marketModulePageSize', 'marketModuleTotal', 'handleMarketModulePageChange');
    }

        /** 执行 marketProjectsPane 方法对应的具体职责。 */
    protected function marketProjectsPane(): array
    {
        return $this->marketPane('marketProjects', 'marketProjectType', 'marketProjectKeyword', 'marketProjectTypeOptions', 'searchMarketProjects', 'project', 'marketProjectPage', 'marketProjectPageSize', 'marketProjectTotal', 'handleMarketProjectPageChange');
    }

        /** 执行 marketPane 方法对应的具体职责。 */
    protected function marketPane(string $itemsPath, string $typePath, string $keywordPath, string $optionsPath, string $searchMethod, string $kind, string $pagePath, string $pageSizePath, string $totalPath, string $pageMethod): array
    {
        return [
            Flex::make()
                ->vertical()
                ->props(['style' => ['height' => '700px', 'overflow' => 'hidden']])
                ->children([
                    Space::make()
                        ->props(['style' => 'margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #eef2f6;'])
                        ->children([
                            Select::make()
                                ->model(['value' => $typePath])
                                ->props([
                                    'placeholder' => $kind === 'project' ? '全部项目分类' : '全部模块分类',
                                    'clearable' => false,
                                    'options' => "{{ {$optionsPath} }}",
                                    'style' => ['width' => '160px'],
                                ]),
                            Input::make()
                                ->model(['value' => $keywordPath])
                                ->props([
                                    'placeholder' => $kind === 'project' ? '搜索项目名称、ID 或描述' : '搜索模块名称、ID 或描述',
                                    'clearable' => true,
                                    'style' => ['width' => '280px'],
                                ]),
                            Button::make()->type('primary')->on('click', ['call' => $searchMethod])->text('搜索'),
                        ]),
                    $this->marketCardGrid($itemsPath, $kind),
                    $this->marketPagination($pagePath, $pageSizePath, $totalPath, $pageMethod),
                ]),
        ];
    }

        /** 执行 marketCardGrid 方法对应的具体职责。 */
    protected function marketCardGrid(string $itemsPath, string $kind): Html
    {
        $detailMethod = $kind === 'project' ? 'showMarketProjectDetail' : 'showMarketModuleDetail';
        $emptyText = $kind === 'project' ? '暂无匹配项目' : '暂无匹配模块';
        $icon = $kind === 'project' ? 'carbon:template' : 'carbon:cube';

        return Html::div()
            ->props(['style' => [
                'flex' => '1 1 0%',
                'overflowY' => 'auto',
                'display' => 'grid',
                'gridTemplateColumns' => 'repeat(4, minmax(0, 1fr))',
                'gridAutoRows' => '136px',
                'gap' => '10px',
                'alignContent' => 'start',
                'padding' => '2px 4px 2px 0',
            ]])
            ->children([
                Card::make()
                    ->for("item in {$itemsPath}", 'item.id')
                    ->hoverable()
                    ->bordered(true)
                    ->size('small')
                    ->props([
                        'style' => ['height' => '136px', 'cursor' => 'pointer', 'borderColor' => '#e5e7eb', 'background' => '#ffffff'],
                        'content-style' => ['height' => '100%', 'padding' => '10px 12px', 'boxSizing' => 'border-box'],
                    ])
                    ->on('click', ['call' => $detailMethod, 'args' => ['{{ item }}']])
                    ->children([
                        Flex::make()->props(['align' => 'center', 'style' => ['gap' => '10px', 'marginBottom' => '7px']])->children([
                            Avatar::make()->if('item.logo')->props(['src' => '{{ item.logo }}', 'size' => 36, 'objectFit' => 'contain', 'style' => ['background' => '#f8fafc', 'border' => '1px solid #eef2f6']]),
                            SvgIcon::make($icon)->if('!item.logo')->props(['class' => 'text-2xl text-primary']),
                            Html::div()->props(['style' => ['minWidth' => 0, 'flex' => 1]])->children([
                                Html::div()->props(['style' => ['fontWeight' => 600, 'fontSize' => '14px', 'lineHeight' => '20px', 'whiteSpace' => 'nowrap', 'overflow' => 'hidden', 'textOverflow' => 'ellipsis']])->children(['{{ item.title }}']),
                                Html::div()->props(['style' => ['fontSize' => '12px', 'lineHeight' => '18px', 'color' => '#667085', 'whiteSpace' => 'nowrap', 'overflow' => 'hidden', 'textOverflow' => 'ellipsis']])->children(['{{ item.id }}']),
                            ]),
                        ]),
                        Html::div()->props(['style' => ['height' => '38px', 'fontSize' => '12px', 'lineHeight' => '19px', 'color' => '#475467', 'overflow' => 'hidden']])->children(['{{ item.summary || "暂无简介" }}']),
                        Flex::make()->props(['justify' => 'space-between', 'align' => 'center', 'style' => ['marginTop' => '8px']])->children([
                            Tag::make()->props(['size' => 'small', 'bordered' => false])->children(['{{ item.type_label || item.type || "-" }}']),
                            Tag::make()->props(['size' => 'small', 'type' => '{{ item.installed ? "success" : "default" }}'])->children(['{{ item.installed ? "已安装" : item.version }}']),
                        ]),
                    ]),
                Html::div()
                    ->if("!{$itemsPath} || {$itemsPath}.length === 0")
                    ->props(['style' => ['gridColumn' => '1 / -1', 'height' => '260px', 'display' => 'flex', 'alignItems' => 'center', 'justifyContent' => 'center', 'color' => '#667085']])
                    ->children([$emptyText]),
            ]);
    }

        /** 执行 marketPagination 方法对应的具体职责。 */
    protected function marketPagination(string $pagePath, string $pageSizePath, string $totalPath, string $handler): Flex
    {
        return Flex::make()
            ->props(['justify' => 'end', 'align' => 'center', 'style' => ['height' => '48px', 'flex' => '0 0 48px', 'paddingTop' => '10px', 'borderTop' => '1px solid #e5e7eb', 'boxSizing' => 'border-box', 'background' => '#fff']])
            ->children([
                Pagination::make()
                    ->props([
                        'page' => "{{ {$pagePath} }}",
                        'pageSize' => "{{ {$pageSizePath} }}",
                        'itemCount' => "{{ {$totalPath} }}",
                        'showSizePicker' => false,
                    ])
                    ->on('update:page', ['call' => $handler, 'args' => ['{{ $event }}']]),
            ]);
    }

        /** 执行 marketDetailModal 方法对应的具体职责。 */
    protected function marketDetailModal(): Modal
    {
        return Modal::make()
            ->show('marketDetailVisible')
            ->title('{{ marketDetailKind === "project" ? "项目详情" : "模块详情" }}')
            ->preset('card')
            ->style(['width' => '720px'])
            ->on('update:show', SetAction::make('marketDetailVisible', '{{ $event }}'))
            ->children([
                Flex::make()->vertical()->props(['style' => ['gap' => '14px']])->children([
                    Flex::make()->props(['align' => 'center', 'style' => ['gap' => '12px']])->children([
                        Avatar::make()->if('marketDetailItem?.logo')->props(['src' => '{{ marketDetailItem.logo }}', 'size' => 48, 'objectFit' => 'contain']),
                        SvgIcon::make('carbon:cube')->if('!marketDetailItem?.logo')->props(['class' => 'text-4xl text-primary']),
                        Html::div()->children([
                            Html::div()->props(['style' => ['fontSize' => '18px', 'fontWeight' => 700]])->children(['{{ marketDetailItem?.title || "-" }}']),
                            Html::div()->props(['style' => ['fontSize' => '12px', 'color' => '#667085']])->children(['{{ marketDetailItem?.id || "-" }}']),
                        ]),
                    ]),
                    Html::div()->props(['style' => ['lineHeight' => '22px', 'color' => '#344054']])->children(['{{ marketDetailItem?.summary || "暂无简介" }}']),
                    Space::make()->props(['wrap' => true])->children([
                        Tag::make()->children(['{{ marketDetailItem?.type_label || marketDetailItem?.type || "-" }}']),
                        Tag::make()->children(['版本 {{ marketDetailItem?.version || "-" }}']),
                        Tag::make()->children(['{{ marketDetailItem?.license || "-" }}']),
                        Tag::make()->if('marketDetailItem?.author')->children(['{{ marketDetailItem.author }}']),
                    ]),
                    Html::div()->props(['style' => ['fontSize' => '12px', 'color' => '#667085']])->children(['{{ marketDetailKind === "project" ? "项目可作为多个模块的组合安装入口。" : "模块安装请在本地命令行执行对应安装命令。" }}']),
                    Space::make()->props(['justify' => 'end'])->children([
                        Button::make()->if('marketDetailKind === "module" && !marketDetailItem?.installed')->type('primary')->on('click', ['call' => 'handleInstallMarketModule'])->text('安装'),
                        Button::make()->if('marketDetailKind === "project"')->type('primary')->on('click', ['call' => 'handleInstallMarketProject'])->text('安装项目'),
                        Button::make()->on('click', SetAction::make('marketDetailVisible', false))->text('关闭'),
                    ]),
                ]),
            ]);
    }
}
