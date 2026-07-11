<?php

namespace Lartrix\Modules\Registry;

use JsonException;
use Lartrix\Modules\Manifest\ModuleManifest;
use Lartrix\Modules\Manifest\ModuleManifestValidator;

/** 核对暂存包的模块 ID、版本和适配器，防止错误包被复制或用于更新。 */
class RegistryStagedManifestVerifier
{
    /** 初始化当前对象及其依赖。 */
    public function __construct(private readonly string $language, private readonly string $framework)
    {
    }

    /**
     * 校验数据或发布包的真实性与一致性。
     * @return array<string, mixed>
     */
    public function verify(string $stagePath, string $manifest, string $expectedId, string $expectedVersion): array
    {
        $manifestPath = $stagePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $manifest);
        if (!is_file($manifestPath)) {
            return $this->failure('manifest_missing', 'Staged package manifest file does not exist.');
        }

        try {
            $data = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->failure('manifest_json_invalid', 'Staged package manifest is not valid JSON.');
        }

        if (!is_array($data)) {
            return $this->failure('manifest_json_invalid', 'Staged package manifest must be a JSON object.');
        }

        $errors = ModuleManifestValidator::validateForAdapter($data, $this->language, $this->framework);
        if ($errors !== []) {
            return [
                'ok' => false,
                'reason' => 'manifest_adapter_invalid',
                'message' => 'Staged package manifest does not support the current adapter.',
                'manifest_id' => is_string($data['id'] ?? null) ? $data['id'] : null,
                'manifest_version' => is_string($data['version'] ?? null) ? $data['version'] : null,
                'adapter_status' => null,
                'security' => is_array($data['security'] ?? null) ? $data['security'] : [],
                'errors' => $errors,
            ];
        }

        $manifestObject = ModuleManifest::fromArray($data);
        if ($manifestObject->id() !== $expectedId) {
            return $this->failure('module_id_mismatch', 'Staged package manifest id does not match registry module id.', $manifestObject);
        }

        if ($manifestObject->version() !== $expectedVersion) {
            return $this->failure('module_version_mismatch', 'Staged package manifest version does not match registry version.', $manifestObject);
        }

        return [
            'ok' => true,
            'reason' => null,
            'message' => 'Staged package manifest matches registry metadata.',
            'manifest_id' => $manifestObject->id(),
            'manifest_version' => $manifestObject->version(),
            'adapter_status' => $manifestObject->adapterStatus(),
            'security' => $manifestObject->security(),
            'errors' => [],
        ];
    }

    /**
     * 执行 failure 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function failure(string $reason, string $message, ?ModuleManifest $manifest = null): array
    {
        return [
            'ok' => false,
            'reason' => $reason,
            'message' => $message,
            'manifest_id' => $manifest?->id(),
            'manifest_version' => $manifest?->version(),
            'adapter_status' => $manifest?->adapterStatus(),
            'security' => $manifest?->security() ?? [],
            'errors' => [],
        ];
    }
}
