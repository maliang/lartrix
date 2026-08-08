<?php

namespace Lartrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lartrix\Modules\Registry\RegistryClient;
use Lartrix\Modules\Project\ProjectManifest;

/** 校验并打包项目清单及其覆盖配置，生成可上传到模块市场的发布包。 */
class ProjectPublishCommand extends Command
{
    protected $signature = 'lartrix:project-publish
                            {--manifest=trix-project.json : 项目清单路径}
                            {--registry= : 市场 API 基础 URL}
                            {--auth-key= : Auth Key，默认为 TRIX_AUTH_KEY 配置}
                            {--dry-run : 仅校验，不发布}';

    protected $description = '将根目录的 trix-project.json 项目清单发布到 Trix 市场';

    /** 处理命令或请求的主流程。 */
    public function handle(): int
    {
        $manifestPath = base_path((string) $this->option('manifest'));
        if (!File::exists($manifestPath)) {
            $this->error('Project manifest not found: ' . $manifestPath);

            return self::FAILURE;
        }

        try {
            $manifest = ProjectManifest::load($manifestPath)->toArray();
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $registry = $this->registryUrl();
        $authKey = $this->authKey();
        if ($registry === '' || $authKey === '') {
            $this->error('Please configure registry URL and TRIX_AUTH_KEY.');

            return self::FAILURE;
        }

        $publisher = $this->publisher($registry, $authKey);
        if ($publisher === null) {
            return self::FAILURE;
        }

        $authorError = $this->validateAuthor($manifest, $publisher);
        if ($authorError !== null) {
            $this->error($authorError);

            return self::FAILURE;
        }

        $versionError = $this->validateVersion($registry, $authKey, $manifest);
        if ($versionError !== null) {
            $this->error($versionError);

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Project manifest is publishable.');

            return self::SUCCESS;
        }

        $response = (new RegistryClient($registry, $authKey, 60))->request()
            ->post($registry . '/registry/publish/projects', [
                'manifest' => json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

        if (!$response->successful()) {
            $this->error($response->json('msg') ?: $response->json('message') ?: 'Project publish failed.');

            return self::FAILURE;
        }

        $this->info($response->json('msg') ?: 'Project submitted for review.');

        return self::SUCCESS;
    }

        /** 处理 Registry 地址、认证或请求。 */
    private function registryUrl(): string
    {
        $option = trim((string) ($this->option('registry') ?? ''));

        return rtrim($option !== '' ? $option : (string) config('lartrix.module_market.url', ''), '/');
    }

    /** 读取项目发布使用的 Auth Key。 */
    private function authKey(): string
    {
        $option = trim((string) ($this->option('auth-key') ?? ''));

        return $option !== '' ? $option : trim((string) config('lartrix.module_market.auth_key', ''));
    }

    /**
     * 发布当前模块、项目或资源。
     * @return array<string, mixed>|null
     */
    private function publisher(string $registry, string $authKey): ?array
    {
        $response = (new RegistryClient($registry, $authKey, 30))->request()->get($registry . '/registry/auth/me');
        if (!$response->successful()) {
            $this->error($response->json('msg') ?: 'Auth Key is invalid or has no publish permission.');

            return null;
        }

        $payload = $response->json('data');

        return is_array($payload) ? $payload : null;
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $publisher
     */
    private function validateAuthor(array $manifest, array $publisher): ?string
    {
        $author = $this->normalize($manifest['author'] ?? null);
        $user = is_array($publisher['user'] ?? null) ? $publisher['user'] : [];
        $allowed = array_filter([
            $this->normalize($user['name'] ?? null),
            $this->normalize($user['email'] ?? null),
        ]);

        return in_array($author, $allowed, true) ? null : 'Project author must match the Auth Key user name or email.';
    }

    /**
     * 校验输入数据是否满足当前约束。
     * @param array<string, mixed> $manifest
     */
    private function validateVersion(string $registry, string $authKey, array $manifest): ?string
    {
        $response = (new RegistryClient($registry, $authKey, 30))->request()
            ->get($registry . '/registry/projects/' . rawurlencode((string) $manifest['id']) . '/versions', [
                'page_size' => 1,
                'language' => 'php',
                'framework' => 'laravel',
            ]);

        if (!$response->successful()) {
            return null;
        }

        $remoteVersion = data_get($response->json(), 'data.items.0.version') ?: data_get($response->json(), 'data.version');
        if (is_string($remoteVersion) && $remoteVersion !== '' && !version_compare((string) $manifest['version'], $remoteVersion, '>')) {
            return "Local project version {$manifest['version']} must be higher than registry version {$remoteVersion}.";
        }

        return null;
    }

        /** 将输入值归一化为内部标准格式。 */
    private function normalize(mixed $value): string
    {
        return is_string($value) ? mb_strtolower(trim($value)) : '';
    }
}
