<?php

namespace Lartrix\Modules\Registry;

/** 从 Registry 响应中选择与当前语言、框架匹配且允许安装的适配器。 */
class RegistryAdapterResolver
{
    private const INSTALLABLE_STATUSES = ['stable', 'compatible', 'experimental'];

    /** 初始化当前对象及其依赖。 */
    public function __construct(private readonly string $language, private readonly string $framework)
    {
    }

    /**
     * 解析并返回当前流程所需的目标值。
     * @param array<string, mixed> $versionPayload
     * @return array<string, mixed>
     */
    public function resolve(array $versionPayload): array
    {
        $adapters = $versionPayload['adapters'] ?? [];
        $adapter = $this->findAdapter($adapters);

        if ($adapter === null) {
            return [
                'installable' => false,
                'reason' => 'adapter_missing',
                'message' => "Registry module does not provide a {$this->language}/{$this->framework} adapter; available adapters: " . $this->availableAdapters($adapters) . '.',
                'adapter' => null,
            ];
        }

        $status = (string) ($adapter['status'] ?? '');
        if (!in_array($status, self::INSTALLABLE_STATUSES, true)) {
            return [
                'installable' => false,
                'reason' => 'adapter_not_installable',
                'message' => "Registry module {$this->language}/{$this->framework} adapter is {$status}; it cannot be installed.",
                'adapter' => $adapter,
            ];
        }

        return [
            'installable' => true,
            'reason' => null,
            'message' => "Registry module {$this->language}/{$this->framework} adapter is installable.",
            'adapter' => $adapter,
        ];
    }

    /**
     * 查找并返回匹配的业务对象。
     * @param mixed $adapters
     * @return array<string, mixed>|null
     */
    private function findAdapter(mixed $adapters): ?array
    {
        if (!is_array($adapters)) {
            return null;
        }

        foreach ($adapters as $adapter) {
            if (!is_array($adapter)) {
                continue;
            }

            if (($adapter['language'] ?? null) === $this->language && ($adapter['framework'] ?? null) === $this->framework) {
                return $adapter;
            }
        }

        return null;
    }

    /** 汇总响应中可用的语言和框架适配器。 */
    private function availableAdapters(mixed $adapters): string
    {
        if (!is_array($adapters)) {
            return 'none';
        }

        $available = [];
        foreach ($adapters as $adapter) {
            if (
                is_array($adapter)
                && isset($adapter['language'], $adapter['framework'])
                && is_string($adapter['language'])
                && is_string($adapter['framework'])
            ) {
                $available[] = $adapter['language'] . '/' . $adapter['framework'];
            }
        }

        return $available === [] ? 'none' : implode(', ', array_values(array_unique($available)));
    }
}
