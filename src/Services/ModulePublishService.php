<?php

namespace Lartrix\Services;

use Illuminate\Support\Facades\File;
use Lartrix\Models\Module;
use Lartrix\Modules\Manifest\ModuleManifestLoader;
use Lartrix\Modules\Project\ProjectManifestValidator;
use Lartrix\Modules\Registry\RegistryClient;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Throwable;
use ZipArchive;

/** 负责本地模块与项目的发布校验、打包及发布状态计算。 */
class ModulePublishService
{
    use InteractsWithModuleMarket;
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

        try {
            $manifestObject = (new ModuleManifestLoader())->loadFromPath($modulePath);
        } catch (\InvalidArgumentException $e) {
            error($e->getMessage(), null, 40000);
        }
        if (!$manifestObject) {
            error('module.json 缺少合法的 trix 节点', null, 40000);
        }
        $manifest = $manifestObject->toArray();
        $packageManifest = json_decode((string) File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);

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
            $response = (new RegistryClient($registry, $authKey, 60))->request()
                ->attach('package', File::get($zipPath), basename($zipPath))
                ->post($registry . '/registry/publish/modules', [
                    'manifest' => json_encode($packageManifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
        } catch (Throwable $e) {
            error($e->getMessage(), null, 50000);
        }

        if (!$response->successful()) {
            error($response->json('msg') ?: $response->json('message') ?: '上传失败', $response->json(), 50000);
        }

        return success('已上传到 Registry，等待审核', $response->json('data'));
    }


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
            $response = (new RegistryClient($registry, $authKey, 60))->request()
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


    public function withPublishState(array $modules): array
    {
        $publisher = $this->registryPublisherOrNull();

        return array_map(function (array $module) use ($publisher): array {
            $config = is_array($module['config'] ?? null) ? $module['config'] : [];
            $localVersion = (string) ($module['version'] ?? '');
            $registryId = is_string($module['registry_id'] ?? null) ? trim($module['registry_id']) : '';
            $authorOk = $publisher !== null && $this->validateLocalAuthor($config, $publisher) === null;
            $remoteVersion = $registryId !== '' ? $this->registryLatestModuleVersion($registryId) : null;
            $versionIncreased = $remoteVersion === null || ($localVersion !== '' && version_compare($localVersion, $remoteVersion, '>'));

            $module['registry_id'] = $registryId;
            $module['remote_version'] = $remoteVersion;
            $module['can_publish'] = $authorOk && $versionIncreased;
            $module['publish_status'] = $this->publishStatus($publisher, $authorOk, $localVersion, $remoteVersion, $versionIncreased);

            return $module;
        }, $modules);
    }


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


    protected function registryPublisherOrNull(): ?array
    {
        try {
            return $this->registryPublisher();
        } catch (\Throwable) {
            return null;
        }
    }


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


    protected function validateProjectManifest(array $manifest): ?string
    {
        $errors = ProjectManifestValidator::validate($manifest);

        return $errors === [] ? null : 'trix-project.json 校验失败：' . implode('；', $errors);
    }


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


    protected function manifestRegistryId(array $manifest, array $module): string
    {
        foreach ([$manifest['id'] ?? null, $module['registry_id'] ?? null] as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }


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


    protected function normalizeAuthor(mixed $value): string
    {
        return is_string($value) ? mb_strtolower(trim($value)) : '';
    }


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
            if ($this->isSensitiveProjectPath($relative)) {
                continue;
            }

            $zip->addFile($file->getRealPath(), $rootName . '/' . $relative);
        }

        $zip->close();
    }


    protected function isSensitiveProjectPath(string $relative): bool
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        return (bool) preg_match(
            '#(^|/)(\.env(?:\..*)?|auth\.json|\.npmrc|\.pypirc|id_rsa|id_ed25519)$|^(vendor|node_modules|\.git|\.idea|\.vscode|storage|bootstrap/cache|database/[^/]+\.sqlite)(/|$)#i',
            $relative
        );
    }


    protected function safePackageName(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: 'package';
    }

}
