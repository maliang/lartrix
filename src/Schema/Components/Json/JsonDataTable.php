<?php

namespace Lartrix\Schema\Components\Json;

use Lartrix\Schema\Components\Component;

/**
 * JsonDataTable - trix JSON 数据表格组件
 */
class JsonDataTable extends Component
{
    public function __construct()
    {
        parent::__construct('JsonDataTable');
    }

    public static function make(): static
    {
        return new static();
    }

    public function schema(array|string $schema): static
    {
        return $this->props([
            'schema' => is_string($schema) ? "{{ $schema }}" : $schema
        ]);
    }

    /**
     * 设置表格数据源（表达式路径）
     */
    public function dataSource(string $dataPath): static
    {
        return $this->props(['data' => "{{ $dataPath }}"]);
    }

    /**
     * 设置表格数据（静态数组）
     */
    public function tableData(array $data): static
    {
        return $this->props(['data' => $data]);
    }

    public function loading(bool|string $loading = true): static
    {
        return $this->props(['loading' => $loading]);
    }
}
