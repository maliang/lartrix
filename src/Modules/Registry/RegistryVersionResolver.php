<?php

namespace Lartrix\Modules\Registry;

/** 按版本约束和发布状态从 Registry 版本列表中解析可安装版本。 */
class RegistryVersionResolver
{
    /** 初始化当前对象及其依赖。 */
    public function __construct(private readonly string $language, private readonly string $framework)
    {
    }

    /**
     * 解析并返回当前流程所需的目标值。
     * @param array<string, mixed> $registryResponse
     * @return array<string, mixed>
     */
    public function resolveLatest(array $registryResponse): array
    {
        $version = $this->firstVersionPayload($registryResponse);

        if ($version === null) {
            return [
                'installable' => false,
                'reason' => 'registry_version_missing',
                'message' => 'Registry module has no published versions.',
                'version' => null,
                'adapter' => null,
            ];
        }

        $resolved = (new RegistryAdapterResolver($this->language, $this->framework))->resolve($version);
        $resolved['version'] = $version;

        return $resolved;
    }

    /**
     * 执行 firstVersionPayload 方法对应的具体职责。
     * @param array<string, mixed> $registryResponse
     * @return array<string, mixed>|null
     */
    private function firstVersionPayload(array $registryResponse): ?array
    {
        $data = $registryResponse['data'] ?? null;

        if (is_array($data) && isset($data['items']) && is_array($data['items'])) {
            $first = $data['items'][0] ?? null;
            return is_array($first) ? $first : null;
        }

        return is_array($data) ? $data : null;
    }
}
