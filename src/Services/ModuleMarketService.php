<?php

namespace Lartrix\Services;

use Illuminate\Http\Request;
use Lartrix\Models\Module;
use Lartrix\Modules\Registry\RegistryClient;
use Lartrix\Modules\Registry\RegistryPackagePipeline;
use Lartrix\Modules\Registry\RegistryVersionResolver;
use Lartrix\Support\ModuleMarketTypes;

/** 负责模块市场查询、格式化、下载暂存和项目安装计划获取。 */
class ModuleMarketService
{
    use InteractsWithModuleMarket;

    /** 注入市场 Schema 共享的分类规则。 */
    public function __construct(private readonly ModuleMarketTypes $types)
    {
    }
    public function marketModules(Request $request): array
    {
        return success($this->fetchRegistryItems('/registry/modules', [
            'keyword' => $request->input('keyword', ''),
            'type' => $this->types->normalize($request->input('type', 'all')),
            'language' => $request->input('language', 'php'),
            'framework' => $request->input('framework', 'laravel'),
            'page' => max(1, (int) $request->input('page', 1)),
            'page_size' => 16,
        ], 'module'));
    }


    public function marketProjects(Request $request): array
    {
        return success($this->fetchRegistryItems('/registry/projects', [
            'keyword' => $request->input('keyword', ''),
            'type' => $this->types->normalize($request->input('type', 'all')),
            'language' => $request->input('language', 'php'),
            'framework' => $request->input('framework', 'laravel'),
            'page' => max(1, (int) $request->input('page', 1)),
            'page_size' => 16,
        ], 'project'));
    }


    public function installMarketModule(string $id): array
    {
        $result = $this->stageRegistryModule($id);

        if (!($result['staged'] ?? false)) {
            error((string) ($result['message'] ?? '市场模块安装准备失败'), $result, 40000);
        }

        return success('模块包已下载并暂存，请按返回命令完成本地安装', $result);
    }


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

        return success('已生成项目安装计划，请执行 lartrix:project-install 完成安装。', $response->json('data'));
    }

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
        $prepared = (new RegistryPackagePipeline(new RegistryClient($registry, $this->registryAuthKey())))
            ->prepare($adapter, $moduleId, $versionNumber);
        if (!$prepared['ok']) {
            return ['staged' => false, 'message' => $prepared['message'], 'reason' => $prepared['reason']];
        }

        return [
            'staged' => true,
            'module' => $moduleId,
            'version' => $versionNumber,
            'package_path' => $prepared['package_path'],
            'stage_path' => $prepared['path'],
            'manifest' => $prepared['manifest'],
            'commands' => [
                'php artisan lartrix:module-install ' . $moduleId . ' --from-stage="' . $prepared['path'] . '" --manifest="' . $prepared['manifest'] . '" --version="' . $versionNumber . '" --target-dir="Modules/' . $this->moduleDirectoryName($moduleId) . '"',
                'php artisan module:enable ' . $this->moduleDirectoryName($moduleId),
            ],
        ];
    }


    protected function moduleDirectoryName(string $moduleId): string
    {
        return str_replace(' ', '', ucwords(str_replace(['.', '-', '_'], ' ', $moduleId)));
    }


    protected function fetchRegistryItems(string $endpoint, array $query, string $type): array
    {
        $registry = rtrim((string) config('lartrix.module_market.url', ''), '/');
        if ($registry === '') {
            return $this->emptyRegistryPage($query);
        }

        $request = (new RegistryClient($registry, trim((string) config('lartrix.module_market.auth_key', '')), 15))->request();

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


    protected function emptyRegistryPage(array $query): array
    {
        return [
            'items' => [],
            'page' => (int) ($query['page'] ?? 1),
            'page_size' => (int) ($query['page_size'] ?? 16),
            'total' => 0,
        ];
    }


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
            'type_label' => $this->types->label($moduleType, 'module'),
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


    protected function installedModuleIds(): array
    {
        $ids = [];
        Module::query()->get(['name', 'registry_id'])->each(function (Module $module) use (&$ids): void {
            $this->rememberModuleId($ids, $module->name);
            $this->rememberModuleId($ids, $module->registry_id);
        });

        return $ids;
    }


    protected function rememberModuleId(array &$ids, mixed $value): void
    {
        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $ids[strtolower(trim($value))] = true;
    }


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
            'type_label' => $this->types->label($projectType, 'project'),
            'logo' => $item['logo'] ?? $item['icon'] ?? null,
            'thumbnail' => $item['thumbnail'] ?? null,
            'author' => $item['author'] ?? '-',
            'author_url' => $item['author_url'] ?? null,
            'license' => $item['license'] ?? '-',
        ];
    }

}
