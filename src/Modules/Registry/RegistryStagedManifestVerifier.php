<?php

namespace Lartrix\Modules\Registry;

use InvalidArgumentException;
use Lartrix\Modules\Manifest\ModuleManifest;
use Lartrix\Modules\Manifest\ModuleManifestLoader;
use Lartrix\Modules\Manifest\ModuleManifestValidator;

/** 校验暂存包的模块 ID、版本和技术 Adapter 身份。 */
final class RegistryStagedManifestVerifier
{
    /** 初始化校验器。 */
    public function __construct(private readonly string $language, private readonly string $framework)
    {
    }

    /** 校验暂存包并返回合法 Manifest。 */
    public function verify(string $stagePath, string $manifest, string $expectedId, string $expectedVersion): array
    {
        $manifestPath = $stagePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $manifest);
        if (!is_file($manifestPath)) {
            return $this->failure('manifest_missing', 'Staged package manifest file does not exist.');
        }

        try {
            $manifestObject = (new ModuleManifestLoader())->loadFromPath(dirname($manifestPath));
        } catch (InvalidArgumentException $e) {
            return $this->failure('manifest_invalid', $e->getMessage());
        }
        if (!$manifestObject) {
            return $this->failure('trix_node_missing', 'Staged package does not contain module.json.trix.');
        }

        $errors = ModuleManifestValidator::validateForAdapter($manifestObject->toArray(), $this->language, $this->framework);
        if ($errors !== []) {
            return $this->failure('manifest_adapter_invalid', 'Staged package manifest does not support the current adapter.', $manifestObject, $errors);
        }
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
            'manifest_object' => $manifestObject,
            'security' => $manifestObject->security(),
            'errors' => [],
        ];
    }

    /** 构造统一失败结果。 */
    private function failure(string $reason, string $message, ?ModuleManifest $manifest = null, array $errors = []): array
    {
        return [
            'ok' => false,
            'reason' => $reason,
            'message' => $message,
            'manifest_id' => $manifest?->id(),
            'manifest_version' => $manifest?->version(),
            'manifest_object' => $manifest,
            'security' => $manifest?->security() ?? [],
            'errors' => $errors,
        ];
    }
}
