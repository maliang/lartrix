<?php

namespace Lartrix\Modules\Registry;

/** 比较本地与候选版本，生成不修改文件系统的模块更新预览。 */
class RegistryModuleUpdatePlanner
{
    /** 初始化当前对象及其依赖。 */
    public function __construct(private readonly string $language, private readonly string $framework)
    {
    }

    /**
     * 生成当前操作的执行计划。
     * @param array<string, mixed> $currentManifest
     * @param array<string, mixed> $targetVersion
     * @return array<string, mixed>
     */
    public function plan(array $currentManifest, array $targetVersion, bool $allowDowngrade = false): array
    {
        $adapter = (new RegistryAdapterResolver($this->language, $this->framework))->resolve($targetVersion);
        $currentId = $this->stringValue($currentManifest, 'id');
        $targetManifest = is_array($targetVersion['manifest'] ?? null) ? $targetVersion['manifest'] : [];
        $targetId = $this->stringValue($targetManifest, 'id');
        $currentVersion = $this->stringValue($currentManifest, 'version') ?: '0.0.0';
        $targetVersionNumber = $this->stringValue($targetVersion, 'version')
            ?: $this->stringValue($targetManifest, 'version')
            ?: '0.0.0';

        if ($currentId !== null && $targetId !== null && $currentId !== $targetId) {
            return $this->result(false, 'module_id_mismatch', 'Target version belongs to a different module.', $currentVersion, $targetVersionNumber, $adapter);
        }

        if (!$adapter['installable']) {
            return $this->result(false, 'adapter_not_installable', (string) $adapter['message'], $currentVersion, $targetVersionNumber, $adapter);
        }

        $comparison = version_compare($targetVersionNumber, $currentVersion);
        if ($comparison === 0) {
            return $this->result(false, 'already_current', 'Current module version already matches target version.', $currentVersion, $targetVersionNumber, $adapter);
        }

        if ($comparison < 0 && !$allowDowngrade) {
            return $this->result(false, 'downgrade_blocked', 'Target version is older than current version.', $currentVersion, $targetVersionNumber, $adapter);
        }

        if ($comparison < 0) {
            return $this->result(true, 'downgrade_allowed', 'Target version is older than current version and downgrade was explicitly allowed.', $currentVersion, $targetVersionNumber, $adapter);
        }

        return $this->result(true, 'update_available', 'Target version is newer and installable for this adapter.', $currentVersion, $targetVersionNumber, $adapter);
    }

    /**
     * 执行 stringValue 方法对应的具体职责。
     * @param array<string, mixed> $source
     */
    private function stringValue(array $source, string $key): ?string
    {
        return is_string($source[$key] ?? null) ? $source[$key] : null;
    }

    /**
     * 执行 result 方法对应的具体职责。
     * @param array<string, mixed> $adapter
     * @return array<string, mixed>
     */
    private function result(bool $allowed, string $action, string $message, string $currentVersion, string $targetVersion, array $adapter): array
    {
        return [
            'allowed' => $allowed,
            'action' => $action,
            'message' => $message,
            'current_version' => $currentVersion,
            'target_version' => $targetVersion,
            'adapter' => $adapter['adapter'] ?? null,
        ];
    }
}
