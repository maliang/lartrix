<?php

namespace Lartrix\Modules\Registry;

/** 下载 Registry 发布包并校验摘要，只负责生成可信缓存文件，不执行安装。 */
class RegistryPackageDownloader
{
    /** @var callable|null */
    private $fetcher;

    /** 初始化当前对象及其依赖。 */
    public function __construct(private readonly ?string $cachePath = null, ?callable $fetcher = null, private readonly ?string $signatureKey = null)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * 下载并校验 Registry 发布包。
     * @param array<string, mixed> $adapter
     * @return array<string, mixed>
     */
    public function download(array $adapter, string $moduleId, string $version): array
    {
        $packageUrl = (string) ($adapter['package_url'] ?? '');
        if ($packageUrl === '') {
            return $this->failure('package_url_missing', 'Registry adapter does not provide package_url.');
        }

        $content = $this->fetch($packageUrl);
        if ($content === null) {
            return $this->failure('package_fetch_failed', 'Registry adapter package could not be downloaded.');
        }

        $checksum = (string) ($adapter['checksum'] ?? '');
        if ($checksum !== '') {
            $checksumResult = $this->verifyChecksum($content, $checksum);
            if ($checksumResult !== null) {
                return $checksumResult;
            }
        }

        $signatureReason = null;
        $signature = (string) ($adapter['signature'] ?? '');
        if ($signature !== '' && $this->signatureKey !== null && $this->signatureKey !== '') {
            if ($checksum === '') {
                return $this->failure('signature_checksum_missing', 'Registry adapter signature requires checksum payload.');
            }

            $signatureResult = (new RegistryPackageSignatureVerifier())->verify($checksum, $signature, $this->signatureKey);
            if (!$signatureResult['verified']) {
                return $this->failure((string) $signatureResult['reason'], (string) $signatureResult['message']);
            }

            $signatureReason = $signatureResult['reason'];
        }

        $cachePath = $this->cachePath();
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0775, true);
        }

        $language = (string) ($adapter['language'] ?? 'language');
        $framework = (string) ($adapter['framework'] ?? 'framework');
        $filename = $this->safeName($moduleId . '-' . $version . '-' . $language . '-' . $framework) . '.zip';
        $path = $cachePath . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($path, $content);

        return [
            'downloaded' => true,
            'reason' => null,
            'message' => 'Registry adapter package downloaded to cache.',
            'path' => $path,
            'checksum' => $checksum,
            'signature_reason' => $signatureReason,
        ];
    }

        /** 从远端服务获取并解析数据。 */
    private function fetch(string $packageUrl): ?string
    {
        if (is_callable($this->fetcher)) {
            $content = ($this->fetcher)($packageUrl);
            return is_string($content) ? $content : null;
        }

        $content = @file_get_contents($packageUrl);

        return is_string($content) ? $content : null;
    }

    /** 解析 Registry 发布包的本地缓存目录。 */
    private function cachePath(): string
    {
        return $this->cachePath ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-registry-cache';
    }

        /** 生成可安全用于文件系统的名称。 */
    private function safeName(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: 'package';
    }

    /**
     * 校验数据或发布包的真实性与一致性。
     * @return array<string, mixed>|null
     */
    private function verifyChecksum(string $content, string $checksum): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/i', $checksum) === 1) {
            $expected = $checksum;
        } elseif (str_starts_with($checksum, 'sha256:')) {
            $expected = substr($checksum, strlen('sha256:'));
        } else {
            return $this->failure('checksum_unsupported', 'Only sha256 checksums are supported.');
        }

        $actual = hash('sha256', $content);

        if (!hash_equals(strtolower($expected), strtolower($actual))) {
            return $this->failure('checksum_mismatch', 'Registry adapter package checksum does not match.');
        }

        return null;
    }

    /**
     * 执行 failure 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function failure(string $reason, string $message): array
    {
        return [
            'downloaded' => false,
            'reason' => $reason,
            'message' => $message,
            'path' => null,
        ];
    }
}
