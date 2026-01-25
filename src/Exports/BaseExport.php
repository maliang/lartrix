<?php

namespace Lartrix\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * BaseExport - 通用导出基类
 * 
 * 使用 Laravel Excel 实现数据导出
 */
class BaseExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Collection $data;
    protected array $columns;
    protected array $headings = [];
    protected array $keys = [];

    /**
     * @param Collection $data 要导出的数据
     * @param array $columns 列配置，格式：[['key' => 'id', 'title' => 'ID'], ...]
     */
    public function __construct(Collection $data, array $columns)
    {
        $this->data = $data;
        $this->columns = $columns;
        $this->parseColumns();
    }

    /**
     * 解析列配置
     */
    protected function parseColumns(): void
    {
        foreach ($this->columns as $col) {
            // 跳过操作列和选择列
            if (\in_array($col['key'] ?? '', ['actions', 'selection']) || ($col['type'] ?? '') === 'selection') {
                continue;
            }
            
            $this->headings[] = $col['title'] ?? $col['key'];
            $this->keys[] = $col['key'];
        }
    }

    /**
     * 获取数据集合
     */
    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * 获取表头
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * 映射每行数据
     */
    public function map($row): array
    {
        $mapped = [];
        foreach ($this->keys as $key) {
            $value = data_get($row, $key);
            
            // 处理特殊类型
            if ($value instanceof \Illuminate\Support\Collection || $value instanceof \Illuminate\Database\Eloquent\Collection) {
                // Eloquent Collection 转为逗号分隔的字符串（优先取 title，其次 name）
                $value = $value->map(fn($item) => $item->title ?? $item->name ?? '')->filter()->implode(', ');
            } elseif (\is_array($value)) {
                // 普通数组转为逗号分隔的字符串
                $value = collect($value)->map(fn($item) => $item['title'] ?? $item['name'] ?? '')->filter()->implode(', ');
            } elseif (\is_bool($value)) {
                $value = $value ? '是' : '否';
            } elseif ($value === null) {
                $value = '';
            }
            
            $mapped[] = $value;
        }
        return $mapped;
    }

    /**
     * 从查询构建器创建导出实例
     * 
     * @param Builder $query 查询构建器
     * @param array $columns 列配置
     * @param int|null $limit 限制数量（null 表示不限制）
     */
    public static function fromQuery(Builder $query, array $columns, ?int $limit = null): static
    {
        $q = clone $query;
        if ($limit !== null) {
            $q->limit($limit);
        }
        return new static($q->get(), $columns);
    }
}
