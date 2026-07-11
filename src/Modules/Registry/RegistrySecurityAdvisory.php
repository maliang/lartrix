<?php

namespace Lartrix\Modules\Registry;

/** 将模块清单声明的脚本、迁移和外部访问能力归纳为安装风险提示。 */
class RegistrySecurityAdvisory
{
    /** @var array<string, string> */
    private const WARNINGS = [
        'writes_files' => 'writes_files: module declares it may write files.',
        'runs_commands' => 'runs_commands: module declares it may run commands.',
        'external_network' => 'external_network: module declares it may access external network.',
        'requires_secrets' => 'requires_secrets: module declares it may require secrets.',
        'uses_eval' => 'uses_eval: module declares it may evaluate dynamic code.',
    ];

    /**
     * 执行 warnings 方法对应的具体职责。
     * @param array<string, mixed> $security
     * @return array<int, string>
     */
    public function warnings(array $security): array
    {
        $warnings = [];

        foreach (self::WARNINGS as $key => $message) {
            if (($security[$key] ?? false) === true) {
                $warnings[] = $message;
            }
        }

        return $warnings;
    }

    /**
     * 执行 blocksStrict 方法对应的具体职责。
     * @param array<string, mixed> $security
     */
    public function blocksStrict(array $security): bool
    {
        return $this->warnings($security) !== [];
    }
}
