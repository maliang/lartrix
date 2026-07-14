<?php

namespace Lartrix\Modules\Registry;

use Lartrix\Modules\Manifest\ModuleManifest;

/** 直接比较两个合法 Manifest，生成无副作用的更新计划。 */
final class RegistryModuleUpdatePlanner
{
    /** 生成更新、降级或无需操作的计划。 */
    public function plan(ModuleManifest $current, ModuleManifest $target, bool $allowDowngrade = false): array
    {
        $currentVersion = $current->version() ?? '0.0.0';
        $targetVersion = $target->version() ?? '0.0.0';
        if ($current->id() !== $target->id()) {
            return $this->result(false, 'module_id_mismatch', 'Target version belongs to a different module.', $currentVersion, $targetVersion);
        }

        $comparison = version_compare($targetVersion, $currentVersion);
        if ($comparison === 0) {
            return $this->result(false, 'already_current', 'Current module version already matches target version.', $currentVersion, $targetVersion);
        }
        if ($comparison < 0 && !$allowDowngrade) {
            return $this->result(false, 'downgrade_blocked', 'Target version is older than current version.', $currentVersion, $targetVersion);
        }
        if ($comparison < 0) {
            return $this->result(true, 'downgrade_allowed', 'Target version is older and downgrade was explicitly allowed.', $currentVersion, $targetVersion);
        }

        return $this->result(true, 'update_available', 'Target version is newer.', $currentVersion, $targetVersion);
    }

    /** 构造统一计划结果。 */
    private function result(bool $allowed, string $action, string $message, string $currentVersion, string $targetVersion): array
    {
        return compact('allowed', 'action', 'message') + [
            'current_version' => $currentVersion,
            'target_version' => $targetVersion,
        ];
    }
}
