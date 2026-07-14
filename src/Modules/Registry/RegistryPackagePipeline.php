<?php

namespace Lartrix\Modules\Registry;

/** 统一执行模块包下载、安全检查、暂存和 Manifest 校验。 */
final class RegistryPackagePipeline
{
    /** 初始化包准备管线。 */
    public function __construct(
        private readonly RegistryClient $client,
        private readonly string $language = 'php',
        private readonly string $framework = 'laravel',
    ) {
    }

    /** 准备一个经过完整校验的暂存包。 */
    public function prepare(array $adapter, string $moduleId, string $version): array
    {
        $downloader = new RegistryPackageDownloader(
            fetcher: fn (string $url): ?string => $this->client->download($url),
            signatureKey: (string) config('lartrix.module_market.signature_key', ''),
        );
        $download = $downloader->download($adapter, $moduleId, $version);
        if (!($download['downloaded'] ?? false)) {
            return $this->failure($download);
        }

        // Stager 内部只执行一次 ZIP 预检，成功后返回 Manifest 相对路径。
        $stage = (new RegistryPackageStager())->stage((string) $download['path'], $moduleId, $version);
        if (!($stage['staged'] ?? false)) {
            return $this->failure($stage);
        }

        $verify = (new RegistryStagedManifestVerifier($this->language, $this->framework))
            ->verify((string) $stage['path'], (string) $stage['manifest'], $moduleId, $version);
        if (!($verify['ok'] ?? false)) {
            return $this->failure($verify);
        }

        return [
            'ok' => true,
            'reason' => null,
            'message' => '模块包已下载、暂存并通过校验。',
            'path' => $stage['path'],
            'manifest' => $stage['manifest'],
            'security' => $verify['security'] ?? [],
            'package_path' => $download['path'],
        ];
    }

    /** 将底层失败结构转换为管线统一结果。 */
    private function failure(array $result): array
    {
        return [
            'ok' => false,
            'reason' => $result['reason'] ?? 'package_prepare_failed',
            'message' => $result['message'] ?? '模块包准备失败。',
            'path' => null,
            'manifest' => null,
            'security' => [],
        ];
    }
}
