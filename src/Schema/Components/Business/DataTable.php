<?php

namespace Lartrix\Schema\Components\Business;

use Lartrix\Schema\Components\Component;

/**
 * DataTable - 数据表格组件
 *
 * 封装 JsonDataTable，支持通过 JSON schema 的 slots 配置来渲染自定义列内容
 * 对应前端 trix 的 JsonDataTable 组件
 *
 * 列配置支持两种方式：
 * 1. 简单方式：直接传入列数组
 *    ->columns([
 *        ['key' => 'id', 'title' => 'ID', 'width' => 80],
 *        ['key' => 'name', 'title' => '名称'],
 *    ])
 *
 * 2. 带插槽方式（类似 CrudPage）：
 *    ->columns([
 *        ['key' => 'id', 'title' => 'ID', 'width' => 80],
 *        ['key' => 'status', 'title' => '状态', 'slot' => [
 *            SwitchC::make()->props(['value' => '{{ slotData.row.status }}'])
 *        ]],
 *        ['key' => 'actions', 'title' => '操作', 'slot' => [
 *            Space::make()->children([...])
 *        ]],
 *    ])
 */
class DataTable extends Component
{
    protected array $tableSlots = [];

    public function __construct()
    {
        parent::__construct('JsonDataTable');
    }

    public static function make(): static
    {
        return new static();
    }

    /**
     * 设置表格列配置（支持 slot 配置）
     *
     * 示例：
     * ->columns([
     *     ['key' => 'id', 'title' => 'ID', 'width' => 80],
     *     ['key' => 'status', 'title' => '状态', 'slot' => [SwitchC::make()->...]],
     *     ['key' => 'actions', 'title' => '操作', 'slot' => [Space::make()->...], 'slotProps' => 'row'],
     * ])
     *
     * @param array|string $columns 列配置数组或表达式
     */
    public function columns(array|string $columns): static
    {
        if (is_string($columns)) {
            return $this->props(['columns' => "{{ $columns }}"]);
        }

        $processedColumns = [];
        $this->tableSlots = [];

        foreach ($columns as $col) {
            if (isset($col['slot'])) {
                $this->tableSlots[$col['key']] = [
                    'content' => $col['slot'],
                    'slotProps' => $col['slotProps'] ?? 'slotData',
                ];
                unset($col['slot'], $col['slotProps']);
            }
            $processedColumns[] = $col;
        }

        $this->props(['columns' => $processedColumns]);

        // 注册插槽
        foreach ($this->tableSlots as $column => $config) {
            $this->slot($column, $config['content'], $config['slotProps']);
        }

        return $this;
    }

    /**
     * 绑定表格数据源（表达式路径）
     */
    public function dataSource(string $dataPath): static
    {
        return $this->props(['data' => "{{ $dataPath }}"]);
    }

    /**
     * 直接设置表格数据（静态数组）
     */
    public function tableData(array $data): static
    {
        return $this->props(['data' => $data]);
    }

    /**
     * 设置行 key
     */
    public function rowKey(string|array $key): static
    {
        if (is_string($key) && !str_starts_with($key, '{{')) {
            return $this->props(['rowKey' => "{{ row => row.{$key} }}"]);
        }
        return $this->props(['rowKey' => $key]);
    }

    /**
     * 设置加载状态
     */
    public function loading(bool|string $loading = true): static
    {
        if (is_string($loading)) {
            return $this->props(['loading' => "{{ $loading }}"]);
        }
        return $this->props(['loading' => $loading]);
    }

    // ========== 分页相关 ==========

    /**
     * 设置分页配置
     *
     * @param array|string|bool $pagination 分页配置，false 表示不显示分页
     */
    public function pagination(array|string|bool $pagination): static
    {
        return $this->props(['pagination' => $pagination]);
    }

    /**
     * 当表格数据只有一页时是否显示分页
     */
    public function paginateSinglePage(bool $show = true): static
    {
        return $this->props(['paginateSinglePage' => $show]);
    }

    /**
     * 是否使用远程分页/排序/过滤
     */
    public function remote(bool $remote = true): static
    {
        return $this->props(['remote' => $remote]);
    }

    /**
     * 过滤时分页行为
     *
     * @param string $behavior 'first' 跳转到第一页，'current' 保持当前页
     */
    public function paginationBehaviorOnFilter(string $behavior): static
    {
        return $this->props(['paginationBehaviorOnFilter' => $behavior]);
    }

    // ========== 尺寸/样式相关 ==========

    /**
     * 设置表格尺寸
     *
     * @param string $size 'small' | 'medium' | 'large'
     */
    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    /**
     * 是否显示边框
     */
    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }

    /**
     * 是否显示底部边框
     */
    public function bottomBordered(bool $bordered = true): static
    {
        return $this->props(['bottomBordered' => $bordered]);
    }

    /**
     * 是否显示斑马纹
     */
    public function striped(bool $striped = true): static
    {
        return $this->props(['striped' => $striped]);
    }

    /**
     * 是否单行显示（不换行）
     */
    public function singleLine(bool $singleLine = true): static
    {
        return $this->props(['singleLine' => $singleLine]);
    }

    /**
     * 是否单列模式
     */
    public function singleColumn(bool $singleColumn = true): static
    {
        return $this->props(['singleColumn' => $singleColumn]);
    }

    /**
     * 设置表格最小高度
     */
    public function minHeight(int|string $height): static
    {
        return $this->props(['minHeight' => $height]);
    }

    /**
     * 设置表格最大高度
     */
    public function maxHeight(int|string $height): static
    {
        return $this->props(['maxHeight' => $height]);
    }

    /**
     * 设置表格布局方式
     *
     * @param string $layout 'auto' | 'fixed'
     */
    public function tableLayout(string $layout): static
    {
        return $this->props(['tableLayout' => $layout]);
    }

    /**
     * 设置横向滚动宽度
     */
    public function scrollX(int|string $width): static
    {
        return $this->props(['scrollX' => $width]);
    }

    // ========== 虚拟滚动相关 ==========

    /**
     * 是否启用虚拟滚动
     */
    public function virtualScroll(bool $enabled = true): static
    {
        return $this->props(['virtualScroll' => $enabled]);
    }

    /**
     * 是否启用横向虚拟滚动
     */
    public function virtualScrollX(bool $enabled = true): static
    {
        return $this->props(['virtualScrollX' => $enabled]);
    }

    /**
     * 是否启用表头虚拟滚动
     */
    public function virtualScrollHeader(bool $enabled = true): static
    {
        return $this->props(['virtualScrollHeader' => $enabled]);
    }

    /**
     * 设置表头高度（虚拟滚动时使用）
     */
    public function headerHeight(int $height): static
    {
        return $this->props(['headerHeight' => $height]);
    }

    /**
     * 设置行高计算函数（虚拟滚动时使用）
     */
    public function heightForRow(string $expression): static
    {
        return $this->props(['heightForRow' => $expression]);
    }

    /**
     * 设置最小行高（虚拟滚动时使用）
     */
    public function minRowHeight(int $height): static
    {
        return $this->props(['minRowHeight' => $height]);
    }

    // ========== 树形/层级相关 ==========

    /**
     * 是否级联选择子节点
     */
    public function cascade(bool $cascade = true): static
    {
        return $this->props(['cascade' => $cascade]);
    }

    /**
     * 设置子节点字段名
     */
    public function childrenKey(string $key): static
    {
        return $this->props(['childrenKey' => $key]);
    }

    /**
     * 设置树形结构缩进
     */
    public function indent(int $indent): static
    {
        return $this->props(['indent' => $indent]);
    }

    /**
     * 是否允许选中未加载的节点
     */
    public function allowCheckingNotLoaded(bool $allow = true): static
    {
        return $this->props(['allowCheckingNotLoaded' => $allow]);
    }

    /**
     * 展开行是否吸顶
     */
    public function stickyExpandedRows(bool $sticky = true): static
    {
        return $this->props(['stickyExpandedRows' => $sticky]);
    }

    /**
     * 是否默认展开所有行
     */
    public function defaultExpandAll(bool $expand = true): static
    {
        return $this->props(['defaultExpandAll' => $expand]);
    }

    /**
     * 设置默认展开的行 keys
     */
    public function defaultExpandedRowKeys(array|string $keys): static
    {
        if (is_string($keys)) {
            return $this->props(['defaultExpandedRowKeys' => "{{ $keys }}"]);
        }
        return $this->props(['defaultExpandedRowKeys' => $keys]);
    }

    /**
     * 设置展开的行 keys（受控）
     */
    public function expandedRowKeys(array|string $keys): static
    {
        if (is_string($keys)) {
            return $this->props(['expandedRowKeys' => "{{ $keys }}"]);
        }
        return $this->props(['expandedRowKeys' => $keys]);
    }

    // ========== 选择相关 ==========

    /**
     * 设置默认选中的行 keys
     */
    public function defaultCheckedRowKeys(array|string $keys): static
    {
        if (is_string($keys)) {
            return $this->props(['defaultCheckedRowKeys' => "{{ $keys }}"]);
        }
        return $this->props(['defaultCheckedRowKeys' => $keys]);
    }

    /**
     * 设置选中的行 keys（受控）
     */
    public function checkedRowKeys(array|string $keys): static
    {
        if (is_string($keys)) {
            return $this->props(['checkedRowKeys' => "{{ $keys }}"]);
        }
        return $this->props(['checkedRowKeys' => $keys]);
    }

    // ========== 行配置相关 ==========

    /**
     * 设置行类名
     */
    public function rowClassName(string $className): static
    {
        return $this->props(['rowClassName' => $className]);
    }

    /**
     * 设置行属性
     */
    public function rowProps(array|string $rowProps): static
    {
        return $this->props(['rowProps' => $rowProps]);
    }

    // ========== 汇总行相关 ==========

    /**
     * 设置汇总行配置
     */
    public function summary(array|string $summary): static
    {
        return $this->props(['summary' => $summary]);
    }

    /**
     * 设置汇总行位置
     *
     * @param string $placement 'top' | 'bottom'
     */
    public function summaryPlacement(string $placement): static
    {
        return $this->props(['summaryPlacement' => $placement]);
    }

    // ========== 渲染相关 ==========

    /**
     * 设置单元格渲染函数
     */
    public function renderCell(string $expression): static
    {
        return $this->props(['renderCell' => $expression]);
    }

    /**
     * 设置展开图标渲染函数
     */
    public function renderExpandIcon(string $expression): static
    {
        return $this->props(['renderExpandIcon' => $expression]);
    }

    /**
     * 设置加载动画配置
     */
    public function spinProps(array $props): static
    {
        return $this->props(['spinProps' => $props]);
    }

    /**
     * 设置滚动条配置
     */
    public function scrollbarProps(array $props): static
    {
        return $this->props(['scrollbarProps' => $props]);
    }

    /**
     * 设置过滤图标弹出框配置
     */
    public function filterIconPopoverProps(array $props): static
    {
        return $this->props(['filterIconPopoverProps' => $props]);
    }

    // ========== 导出相关 ==========

    /**
     * 设置获取 CSV 单元格内容的函数
     */
    public function getCsvCell(string $expression): static
    {
        return $this->props(['getCsvCell' => $expression]);
    }

    /**
     * 设置获取 CSV 表头内容的函数
     */
    public function getCsvHeader(string $expression): static
    {
        return $this->props(['getCsvHeader' => $expression]);
    }

    // ========== 异步加载相关 ==========

    /**
     * 设置异步加载子节点数据的回调函数（用于树形数据）
     */
    public function onLoad(string $expression): static
    {
        return $this->props(['onLoad' => $expression]);
    }

    // ========== 弹性高度 ==========

    /**
     * 设置弹性高度
     *
     * 为 true 时表格高度自适应父容器
     */
    public function flexHeight(bool $flexHeight = true): static
    {
        return $this->props(['flexHeight' => $flexHeight]);
    }
}
