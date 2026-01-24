<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NDataTable - Naive UI 数据表格组件
 */
class NDataTable extends Component
{
    public function __construct()
    {
        parent::__construct('NDataTable');
    }

    public static function make(): static
    {
        return new static();
    }

    public function columns(array|string $columns): static
    {
        return $this->props([
            'columns' => is_string($columns) ? "{{ $columns }}" : $columns
        ]);
    }

    /**
     * 绑定表格数据源
     */
    public function dataSource(string $dataPath): static
    {
        return $this->props(['data' => "{{ $dataPath }}"]);
    }

    /**
     * 直接设置表格数据
     */
    public function tableData(array $data): static
    {
        return $this->props(['data' => $data]);
    }

    public function rowKey(string $key): static
    {
        return $this->props(['row-key' => $key]);
    }

    public function loading(bool|string $loading = true): static
    {
        return $this->props(['loading' => $loading]);
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }

    public function singleLine(bool $singleLine = true): static
    {
        return $this->props(['single-line' => $singleLine]);
    }

    public function striped(bool $striped = true): static
    {
        return $this->props(['striped' => $striped]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function pagination(array|string|bool $pagination): static
    {
        return $this->props(['pagination' => $pagination]);
    }

    public function scrollX(int|string $width): static
    {
        return $this->props(['scroll-x' => $width]);
    }

    public function maxHeight(int|string $height): static
    {
        return $this->props(['max-height' => $height]);
    }

    public function checkedRowKeys(string $keys): static
    {
        return $this->props(['checked-row-keys' => "{{ $keys }}"]);
    }

    public function rowClassName(string $className): static
    {
        return $this->props(['row-class-name' => $className]);
    }
}
