<?php

namespace Lartrix\Modules\Manifest;

use InvalidArgumentException;

/** 表示经过校验且只读的 module.json.trix 模块清单。 */
final class ModuleManifest
{
    /** @param array<string, mixed> $data 已校验的 Trix 模块数据 */
    private function __construct(private readonly array $data)
    {
    }

    /**
     * 校验输入并创建模块清单，阻止无效数据绕过边界。
     *
     * @param array<string, mixed> $data
     */
    public static function fromValidatedArray(array $data): self
    {
        $errors = ModuleManifestValidator::validate($data);

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid Trix manifest: ' . implode('; ', $errors));
        }

        return new self($data);
    }

    public function id(): ?string
    {
        return $this->stringValue('id');
    }

    public function name(): ?string
    {
        return $this->stringValue('name');
    }

    public function version(): ?string
    {
        return $this->stringValue('version');
    }

    public function type(): ?string
    {
        return $this->stringValue('type');
    }

    public function logo(): ?string
    {
        return $this->stringValue('logo');
    }

    public function thumbnail(): ?string
    {
        return $this->stringValue('thumbnail');
    }

    public function author(): ?string
    {
        return $this->stringValue('author');
    }

    public function authorUrl(): ?string
    {
        return $this->stringValue('author_url');
    }

    /** @return array<string, mixed> */
    public function adapter(): array
    {
        return $this->arrayValue('adapter');
    }

    public function adapterLanguage(): ?string
    {
        return $this->stringValueFrom($this->adapter(), 'language');
    }

    public function adapterFramework(): ?string
    {
        return $this->stringValueFrom($this->adapter(), 'framework');
    }

    /** @return array<int, array<string, mixed>> */
    public function menus(): array
    {
        return $this->listValue('menus');
    }

    /** @return array<int, array<string, mixed>> */
    public function permissions(): array
    {
        return $this->listValue('permissions');
    }

    /** @return array<int, array<string, mixed>> */
    public function schemas(): array
    {
        return $this->listValue('schemas');
    }

    /** @return array<string, mixed> */
    public function security(): array
    {
        return $this->arrayValue('security');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    private function stringValue(string $key): ?string
    {
        return $this->stringValueFrom($this->data, $key);
    }

    /** @param array<string, mixed> $data */
    private function stringValueFrom(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /** @return array<string, mixed> */
    private function arrayValue(string $key): array
    {
        $value = $this->data[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /** @return array<int, array<string, mixed>> */
    private function listValue(string $key): array
    {
        $value = $this->data[$key] ?? [];

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_array($item)));
    }
}
