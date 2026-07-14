<?php

namespace Lartrix\Modules\Registry;

use Lartrix\Modules\Manifest\ModuleManifestLoader;

/** 串联更新预览、暂存包校验和目录替换，并确保高风险动作满足执行条件。 */
class RegistryModuleUpdateExecutor
{
    /** 初始化当前对象及其依赖。 */
    public function __construct(private readonly string $language, private readonly string $framework)
    {
    }

    /**
     * 执行当前流程并返回处理结果。
     * @return array<string, mixed>
     */
    public function execute(
        string $currentDir,
        string $sourceDir,
        string $manifest,
        string $moduleId,
        string $targetVersion,
        string $backupDir,
        bool $confirmed,
        bool $allowDowngrade = false
    ): array {
        $preview = $this->preview($currentDir, $sourceDir, $manifest, $moduleId, $targetVersion, $allowDowngrade);
        if (!$preview['allowed']) {
            return [
                'updated' => false,
                'action' => $preview['action'],
                'message' => $preview['message'],
                'current_version' => $preview['current_version'],
                'target_version' => $preview['target_version'],
                'target_path' => null,
                'backup_path' => null,
                'security' => $preview['security'],
                'plan' => $preview['plan'],
            ];
        }

        // 只有 preview 明确允许后才替换目录；替换动作仍要求调用方传入确认和备份目录。
        $moduleSourceDir = dirname($sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $manifest));
        $replace = (new RegistryModuleReplacer())->replace($moduleSourceDir, $currentDir, $backupDir, $confirmed);
        if (!$replace['replaced']) {
            return $this->failure((string) $replace['reason'], (string) $replace['message'], $preview['plan'], null, $replace);
        }

        return [
            'updated' => true,
            'action' => $preview['action'],
            'message' => 'Module update replaced the current directory and created a backup.',
            'current_version' => $preview['current_version'],
            'target_version' => $preview['target_version'],
            'target_path' => $replace['target_path'],
            'backup_path' => $replace['backup_path'],
            'security' => $preview['security'],
            'plan' => $preview['plan'],
        ];
    }

    /**
     * 执行 preview 方法对应的具体职责。
     * @return array<string, mixed>
     */
    public function preview(
        string $currentDir,
        string $sourceDir,
        string $manifest,
        string $moduleId,
        string $targetVersion,
        bool $allowDowngrade = false
    ): array {
        $current = (new ModuleManifestLoader())->loadFromPath($currentDir);
        if ($current === null) {
            return $this->previewFailure('current_manifest_missing', 'Current module manifest file does not exist.', null);
        }

        // 先校验 staging manifest 属于目标模块、目标版本和当前 adapter，再比较版本。
        $verify = (new RegistryStagedManifestVerifier($this->language, $this->framework))->verify($sourceDir, $manifest, $moduleId, $targetVersion);
        if (!$verify['ok']) {
            return $this->previewFailure((string) $verify['reason'], (string) $verify['message'], null, $verify);
        }

        $targetManifest = $verify['manifest_object'] ?? null;
        if (!$targetManifest instanceof \Lartrix\Modules\Manifest\ModuleManifest) {
            return $this->previewFailure('target_manifest_invalid', 'Target module manifest could not be read.', null, $verify);
        }

        $plan = (new RegistryModuleUpdatePlanner())->plan($current, $targetManifest, $allowDowngrade);

        if (!$plan['allowed']) {
            return $this->previewFailure((string) $plan['action'], (string) $plan['message'], $plan, $verify);
        }

        return [
            'allowed' => true,
            'action' => $plan['action'],
            'message' => $plan['message'],
            'current_version' => $plan['current_version'],
            'target_version' => $plan['target_version'],
            'security' => $verify['security'] ?? [],
            'plan' => $plan,
        ];
    }

    /**
     * 执行 failure 方法对应的具体职责。
     * @param array<string, mixed>|null $plan
     * @param array<string, mixed>|null $verify
     * @param array<string, mixed>|null $replace
     * @return array<string, mixed>
     */
    private function failure(string $action, string $message, ?array $plan, ?array $verify = null, ?array $replace = null): array
    {
        return [
            'updated' => false,
            'action' => $action,
            'message' => $message,
            'current_version' => $plan['current_version'] ?? null,
            'target_version' => $plan['target_version'] ?? null,
            'target_path' => $replace['target_path'] ?? null,
            'backup_path' => $replace['backup_path'] ?? null,
            'security' => $verify['security'] ?? [],
            'plan' => $plan,
        ];
    }

    /**
     * 执行 previewFailure 方法对应的具体职责。
     * @param array<string, mixed>|null $plan
     * @param array<string, mixed>|null $verify
     * @return array<string, mixed>
     */
    private function previewFailure(string $action, string $message, ?array $plan, ?array $verify = null): array
    {
        return [
            'allowed' => false,
            'action' => $action,
            'message' => $message,
            'current_version' => $plan['current_version'] ?? null,
            'target_version' => $plan['target_version'] ?? null,
            'security' => $verify['security'] ?? [],
            'plan' => $plan,
        ];
    }
}
