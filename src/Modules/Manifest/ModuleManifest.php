<?php

namespace Lartrix\Modules\Manifest;

/** Trix 模块清单的只读值对象，为安装、市场和模块同步提供统一字段访问。 */
final class ModuleManifest
{
    /**
     * Manifest 是模块包对外的协议对象，只做只读访问和类型收窄。
     * 具体的 Laravel/ThinkPHP 安装细节由 adapter 和安装器解释。
     *
     * @param array<string, mixed> $data
     */
    private function __construct(private readonly array $data)
    {
    }

    /**
     * 执行 fromArray 方法对应的具体职责。
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /** 获取模块唯一标识。 */
    public function id(): ?string
    {
        // id 是跨框架共享的模块唯一标识，不能用本地目录名替代。
        return $this->stringValue('id');
    }

    /** 获取模块名称。 */
    public function name(): ?string
    {
        return $this->stringValue('name');
    }

    /** 获取模块版本。 */
    public function version(): ?string
    {
        return $this->stringValue('version');
    }

    /** 获取模块类型。 */
    public function type(): ?string
    {
        return $this->stringValue('type');
    }

    /** 获取模块 Logo 地址。 */
    public function logo(): ?string
    {
        return $this->stringValue('logo');
    }

    /** 获取模块缩略图地址。 */
    public function thumbnail(): ?string
    {
        return $this->stringValue('thumbnail');
    }

    /** 获取模块作者。 */
    public function author(): ?string
    {
        return $this->stringValue('author');
    }

    /** 获取作者主页地址。 */
    public function authorUrl(): ?string
    {
        return $this->stringValue('author_url');
    }

    /**
     * 执行 adapter 方法对应的具体职责。
     * @return array<string, mixed>
     */
    public function adapter(): array
    {
        // 当前包只声明一个 adapter；完整支持矩阵由 Registry 数据库维护。
        return $this->arrayValue('adapter');
    }

    /** 获取当前适配器声明的语言。 */
    public function adapterLanguage(): ?string
    {
        return $this->stringValueFrom($this->adapter(), 'language');
    }

    /** 获取当前适配器声明的框架。 */
    public function adapterFramework(): ?string
    {
        return $this->stringValueFrom($this->adapter(), 'framework');
    }

    /** 获取当前适配器的可安装状态。 */
    public function adapterStatus(): ?string
    {
        return $this->stringValueFrom($this->adapter(), 'status');
    }

    /**
     * 执行 menus 方法对应的具体职责。
     * @return array<int, array<string, mixed>>
     */
    public function menus(): array
    {
        return $this->listValue('menus');
    }

    /**
     * 执行 permissions 方法对应的具体职责。
     * @return array<int, array<string, mixed>>
     */
    public function permissions(): array
    {
        return $this->listValue('permissions');
    }

    /**
     * 执行 schemas 方法对应的具体职责。
     * @return array<int, array<string, mixed>>
     */
    public function schemas(): array
    {
        return $this->listValue('schemas');
    }

    /**
     * 执行 security 方法对应的具体职责。
     * @return array<string, mixed>
     */
    public function security(): array
    {
        return $this->arrayValue('security');
    }

    /**
     * 将当前对象转换为目标结构。
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /** 读取并清理字符串字段值。 */
    private function stringValue(string $key): ?string
    {
        $value = $this->data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * 执行 stringValueFrom 方法对应的具体职责。
     * @param array<string, mixed> $data
     */
    private function stringValueFrom(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * 执行 arrayValue 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function arrayValue(string $key): array
    {
        $value = $this->data[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * 执行 listValue 方法对应的具体职责。
     * @return array<int, array<string, mixed>>
     */
    private function listValue(string $key): array
    {
        $value = $this->data[$key] ?? [];

        if (!is_array($value)) {
            return [];
        }

        // 过滤掉非对象项，避免脏 manifest 让菜单/权限注册流程抛类型错误。
        return array_values(array_filter($value, static fn ($item): bool => is_array($item)));
    }
}
