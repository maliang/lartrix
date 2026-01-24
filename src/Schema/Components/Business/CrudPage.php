<?php

namespace Lartrix\Schema\Components\Business;

use Lartrix\Schema\Components\Component;
use Lartrix\Schema\Components\NaiveUI\NCard;
use Lartrix\Schema\Components\NaiveUI\NForm;
use Lartrix\Schema\Components\NaiveUI\NFormItem;
use Lartrix\Schema\Components\NaiveUI\NInput;
use Lartrix\Schema\Components\NaiveUI\NSelect;
use Lartrix\Schema\Components\NaiveUI\NButton;
use Lartrix\Schema\Components\NaiveUI\NSpace;
use Lartrix\Schema\Components\NaiveUI\NModal;
use Lartrix\Schema\Components\NaiveUI\NDrawer;
use Lartrix\Schema\Components\NaiveUI\NDrawerContent;
use Lartrix\Schema\Components\Json\JsonDataTable;
use Lartrix\Schema\Components\NaiveUI\NFlex;
use Lartrix\Schema\Components\NaiveUI\NPagination;
use Lartrix\Schema\Components\Common\TableColumnSetting;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;

/**
 * CrudPage - 简化版 CRUD 页面组件
 * 
 * 提供：
 * - 搜索表单
 * - 数据表格（支持分页、列插槽、树形结构）
 * - 工具栏
 * - 弹窗/抽屉
 */
class CrudPage
{
    // 基础配置
    protected string $title = '';
    protected string $apiPrefix = '';
    protected string $rowKey = 'id';
    
    // 表格配置
    protected array $columns = [];
    protected array $tableSlots = [];
    protected int $scrollX = 1000;
    protected bool $paginated = true;
    protected int $defaultPageSize = 15;
    protected array $pageSizes = [10, 20, 50, 100];
    protected bool $showSizePicker = true;
    protected bool $showQuickJumper = false;  // 快速跳跃
    protected bool $showItemCount = false;    // 显示总条数
    protected bool $hideOnSinglePage = true;  // 数据不超过一页时隐藏分页
    
    // 树形配置
    protected bool $isTree = false;
    protected string $childrenKey = 'children';
    protected bool $defaultExpandAll = false;
    protected ?string $expandedRowKeys = null;
    protected ?int $indent = null;
    
    // 表格高度配置
    protected bool $flexHeight = true;
    
    // 搜索配置
    protected array $searchItems = [];
    
    // 工具栏配置
    protected array $toolbarLeft = [];
    protected array $toolbarRight = [];
    
    // 弹窗配置
    protected array $modals = [];
    
    // 抽屉配置
    protected array $drawers = [];
    
    // 额外配置
    protected array $extraData = [];
    protected array $extraMethods = [];

    public function __construct(string $title = '')
    {
        $this->title = $title;
    }

    public static function make(string $title = ''): static
    {
        return new static($title);
    }

    // === 基础配置 ===

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function apiPrefix(string $prefix): static
    {
        $this->apiPrefix = $prefix;
        return $this;
    }

    public function rowKey(string $key): static
    {
        $this->rowKey = $key;
        return $this;
    }

    // === 表格配置 ===

    /**
     * 设置表格列（支持 slot 配置）
     * 
     * 示例：
     * ->columns([
     *     ['key' => 'id', 'title' => 'ID', 'width' => 80],
     *     ['key' => 'status', 'title' => '状态', 'slot' => [NSwitch::make()->...]],
     *     ['key' => 'actions', 'title' => '操作', 'slot' => [NSpace::make()->...]],
     * ])
     */
    public function columns(array $columns): static
    {
        $this->columns = [];
        $this->tableSlots = [];
        
        foreach ($columns as $col) {
            if (isset($col['slot'])) {
                $this->tableSlots[$col['key']] = [
                    'content' => $col['slot'],
                    'slotProps' => $col['slotProps'] ?? 'slotData',
                ];
                unset($col['slot'], $col['slotProps']);
            }
            $this->columns[] = $col;
        }
        
        return $this;
    }

    public function scrollX(int $width): static
    {
        $this->scrollX = $width;
        return $this;
    }

    public function paginated(bool $paginated = true): static
    {
        $this->paginated = $paginated;
        return $this;
    }

    public function defaultPageSize(int $size): static
    {
        $this->defaultPageSize = $size;
        return $this;
    }

    /**
     * 设置分页大小选项
     * 
     * @param array $sizes 分页大小选项，如 [10, 20, 50, 100]
     */
    public function pageSizes(array $sizes): static
    {
        $this->pageSizes = $sizes;
        return $this;
    }

    /**
     * 是否显示分页大小选择器
     */
    public function showSizePicker(bool $show = true): static
    {
        $this->showSizePicker = $show;
        return $this;
    }

    /**
     * 是否显示快速跳跃输入框
     */
    public function showQuickJumper(bool $show = true): static
    {
        $this->showQuickJumper = $show;
        return $this;
    }

    /**
     * 是否显示总条数
     * 
     * 显示格式如：共 100 条
     */
    public function showItemCount(bool $show = true): static
    {
        $this->showItemCount = $show;
        return $this;
    }

    /**
     * 数据不超过一页时是否隐藏分页
     * 
     * @param bool $hide 默认 true，隐藏分页
     */
    public function hideOnSinglePage(bool $hide = true): static
    {
        $this->hideOnSinglePage = $hide;
        return $this;
    }

    // === 树形配置 ===

    /**
     * 启用树形模式
     * 
     * @param string $childrenKey 子节点字段名，默认 'children'
     * @param bool $defaultExpandAll 是否默认展开所有节点
     * @param int|null $indent 缩进宽度（像素）
     * 
     * 示例：
     * ->tree()  // 使用默认配置
     * ->tree('children', true)  // 默认展开所有
     * ->tree('sub_items', false, 24)  // 自定义子节点字段和缩进
     */
    public function tree(string $childrenKey = 'children', bool $defaultExpandAll = false, ?int $indent = null): static
    {
        $this->isTree = true;
        $this->childrenKey = $childrenKey;
        $this->defaultExpandAll = $defaultExpandAll;
        $this->indent = $indent;
        // 树形模式默认不分页
        $this->paginated = false;
        return $this;
    }

    /**
     * 设置展开的行（表达式）
     * 
     * @param string $expression 表达式，如 '{{ expandedKeys }}'
     */
    public function expandedRowKeys(string $expression): static
    {
        $this->expandedRowKeys = $expression;
        return $this;
    }

    /**
     * 设置表格弹性高度
     * 
     * @param bool $flexHeight 是否启用弹性高度，默认 true
     */
    public function flexHeight(bool $flexHeight = true): static
    {
        $this->flexHeight = $flexHeight;
        return $this;
    }

    // === 搜索配置 ===

    /**
     * 设置搜索区域
     * 
     * 示例：
     * ->search([
     *     ['关键词', 'keyword', NInput::make()->props(['clearable' => true])],
     *     ['状态', 'status', NSelect::make()->props(['options' => [...]])],
     * ])
     */
    public function search(array $items): static
    {
        $this->searchItems = $items;
        return $this;
    }

    // === 工具栏配置 ===

    /**
     * 设置工具栏左侧
     * 
     * 内置组件：columnSelector, batchDelete, exportCurrent, exportAll, print
     */
    public function toolbarLeft(array $items): static
    {
        $this->toolbarLeft = $items;
        return $this;
    }

    public function toolbarRight(array $items): static
    {
        $this->toolbarRight = $items;
        return $this;
    }

    // === 弹窗配置 ===

    /**
     * 添加弹窗
     * 
     * @param string $name 弹窗名称（用于生成 {name}Visible 状态）
     * @param string $title 弹窗标题（支持表达式）
     * @param string|array|Component|OptForm $content 弹窗内容
     * @param array $options 额外选项：width, data, onClose
     */
    public function modal(string $name, string $title, string|array|Component|OptForm $content, array $options = []): static
    {
        $this->modals[$name] = [
            'title' => $title,
            'content' => $content,
            'width' => $options['width'] ?? '500px',
            'data' => $options['data'] ?? [],
            'onClose' => $options['onClose'] ?? null,
        ];
        return $this;
    }

    // === 抽屉配置 ===

    /**
     * 添加抽屉
     * 
     * @param string $name 抽屉名称（用于生成 {name}Visible 状态）
     * @param string $title 抽屉标题（支持表达式）
     * @param string|array|Component|OptForm $content 抽屉内容
     * @param array $options 额外选项：width, placement, data, onClose
     */
    public function drawer(string $name, string $title, string|array|Component|OptForm $content, array $options = []): static
    {
        $this->drawers[$name] = [
            'title' => $title,
            'content' => $content,
            'width' => $options['width'] ?? 500,
            'placement' => $options['placement'] ?? 'right',
            'data' => $options['data'] ?? [],
            'onClose' => $options['onClose'] ?? null,
        ];
        return $this;
    }

    // === 额外配置 ===

    public function data(array $data): static
    {
        $this->extraData = array_merge($this->extraData, $data);
        return $this;
    }

    public function method(string $name, array $actions): static
    {
        $this->extraMethods[$name] = $actions;
        return $this;
    }

    public function methods(array $methods): static
    {
        $this->extraMethods = array_merge($this->extraMethods, $methods);
        return $this;
    }

    // === 构建方法 ===

    public function build(): array
    {
        $schema = NCard::make()
            ->props([
                'title' => $this->title,
                // 设置 flex 布局，让内容区域能正确继承高度
                'style' => [
                    'height' => '100%',
                    'display' => 'flex',
                    'flexDirection' => 'column',
                ],
                'contentStyle' => [
                    'flex' => '1 1 0%',
                    'overflow' => 'hidden',
                    'display' => 'flex',
                    'flexDirection' => 'column',
                ],
            ])
            ->data($this->buildData())
            ->methods($this->buildMethods())
            ->onMounted(CallAction::make('loadData'))
            ->children($this->buildChildren());

        return $schema->toArray();
    }

    protected function buildData(): array
    {
        $data = [
            'searchForm' => $this->buildSearchFormData(),
            'tableData' => [],
            'loading' => false,
            'columns' => $this->buildColumnChecks(),
        ];

        if ($this->paginated) {
            $data['pagination'] = [
                'page' => 1,
                'pageSize' => $this->defaultPageSize,
                'total' => 0,
            ];
        }

        // 工具栏相关数据
        $allToolbarItems = array_merge($this->toolbarLeft, $this->toolbarRight);
        foreach ($allToolbarItems as $item) {
            if ($item === 'batchDelete') {
                $data['selectedRowKeys'] = [];
            }
        }

        // 树形数据
        if ($this->isTree && $this->expandedRowKeys) {
            $data['expandedRowKeys'] = [];
        }

        // 弹窗数据
        foreach ($this->modals as $name => $config) {
            $data["{$name}Visible"] = false;
            foreach ($config['data'] as $key => $value) {
                $data[$key] = $value;
            }
        }

        // 抽屉数据
        foreach ($this->drawers as $name => $config) {
            $data["{$name}Visible"] = false;
            foreach ($config['data'] as $key => $value) {
                $data[$key] = $value;
            }
        }

        return array_merge($data, $this->extraData);
    }

    protected function buildSearchFormData(): array
    {
        $data = [];
        foreach ($this->searchItems as $item) {
            $name = $item[1];
            $default = $item[3] ?? '';
            $data[$name] = $default;
        }
        return $data;
    }

    protected function buildColumnChecks(): array
    {
        return array_map(fn($col) => array_merge($col, [
            'title' => $col['title'] ?? $col['key'],
            'checked' => true,
            'visible' => true,
        ]), $this->columns);
    }

    protected function buildMethods(): array
    {
        $methods = [
            'loadData' => $this->buildLoadDataMethod(),
            'search' => $this->buildSearchMethod(),
            'resetSearch' => $this->buildResetSearchMethod(),
        ];

        if ($this->paginated) {
            $methods['handlePageChange'] = [
                SetAction::make('pagination.page', '{{ $event }}'),
                CallAction::make('loadData'),
            ];
            $methods['handlePageSizeChange'] = [
                SetAction::make('pagination.pageSize', '{{ $event }}'),
                SetAction::make('pagination.page', 1),
                CallAction::make('loadData'),
            ];
        }

        // 工具栏相关方法
        $allToolbarItems = array_merge($this->toolbarLeft, $this->toolbarRight);
        foreach ($allToolbarItems as $item) {
            if ($item === 'batchDelete' && !isset($methods['handleSelectionChange'])) {
                $methods['handleSelectionChange'] = [
                    SetAction::make('selectedRowKeys', '{{ $event }}'),
                ];
                $methods['handleBatchDelete'] = [
                    FetchAction::make("{$this->apiPrefix}/batch")
                        ->delete()
                        ->body(['ids' => '{{ selectedRowKeys }}'])
                        ->then([
                            CallAction::make('$message.success', ['批量删除成功']),
                            SetAction::make('selectedRowKeys', []),
                            CallAction::make('loadData'),
                        ])
                        ->catch([
                            CallAction::make('$message.error', ['{{ $error.message || "批量删除失败" }}']),
                        ]),
                ];
            }
            
            if ($item === 'exportCurrent' && !isset($methods['handleExportCurrent'])) {
                $methods['handleExportCurrent'] = [
                    CallAction::make('$message.info', ['导出当前页功能开发中']),
                ];
            }
            
            if ($item === 'exportAll' && !isset($methods['handleExportAll'])) {
                $methods['handleExportAll'] = [
                    CallAction::make('$message.info', ['导出全部功能开发中']),
                ];
            }
            
            if ($item === 'print' && !isset($methods['handlePrint'])) {
                $methods['handlePrint'] = [
                    CallAction::make('$methods.$window.print'),
                ];
            }
        }

        // 弹窗关闭方法
        foreach ($this->modals as $name => $config) {
            $closeMethod = "handle" . ucfirst($name) . "Close";
            $methods[$closeMethod] = $config['onClose'] ?? [
                SetAction::make("{$name}Visible", false),
            ];
        }

        // 抽屉关闭方法
        foreach ($this->drawers as $name => $config) {
            $closeMethod = "handle" . ucfirst($name) . "Close";
            $methods[$closeMethod] = $config['onClose'] ?? [
                SetAction::make("{$name}Visible", false),
            ];
        }

        return array_merge($methods, $this->extraMethods);
    }

    protected function buildLoadDataMethod(): array
    {
        $params = [];
        foreach ($this->searchItems as $item) {
            $name = $item[1];
            $params[$name] = "{{ searchForm.{$name} }}";
        }

        if ($this->paginated) {
            $params['page'] = '{{ pagination.page }}';
            $params['page_size'] = '{{ pagination.pageSize }}';
        }

        // 树形模式直接获取全部数据，分页模式获取列表
        $thenActions = [
            SetAction::make('tableData', $this->paginated 
                ? '{{ $response.data.list || [] }}' 
                : '{{ $response.data || [] }}'
            ),
        ];

        if ($this->paginated) {
            $thenActions[] = SetAction::make('pagination.total', '{{ $response.data.total || 0 }}');
        }

        return [
            SetAction::make('loading', true),
            FetchAction::make($this->apiPrefix)
                ->params($params)
                ->then($thenActions)
                ->catch([
                    CallAction::make('$message.error', ['{{ $error.message || "加载数据失败" }}']),
                ])
                ->finally([
                    SetAction::make('loading', false),
                ]),
        ];
    }

    protected function buildSearchMethod(): array
    {
        $actions = [];
        if ($this->paginated) {
            $actions[] = SetAction::make('pagination.page', 1);
        }
        $actions[] = CallAction::make('loadData');
        return $actions;
    }

    protected function buildResetSearchMethod(): array
    {
        $actions = [];
        foreach ($this->searchItems as $item) {
            $name = $item[1];
            $default = $item[3] ?? '';
            $actions[] = SetAction::make("searchForm.{$name}", $default);
        }
        $actions[] = CallAction::make('search');
        return $actions;
    }

    protected function buildChildren(): array
    {
        $spaceChildren = [];
        
        if (!empty($this->searchItems)) {
            $spaceChildren[] = $this->buildSearchForm();
        }
        
        $toolbar = $this->buildToolbar();
        if ($toolbar) {
            $spaceChildren[] = $toolbar;
        }
        
        $spaceChildren[] = $this->buildDataTable();

        // 分页组件单独放在 NSpace 外部，避免影响表格的 flex 布局
        if ($this->paginated) {
            $spaceChildren[] = $this->buildPagination();
        }

        $children = [
            NSpace::make()
                ->props([
                    'vertical' => true,
                    'size' => 'large',
                    'wrapItem' => false,
                    // 设置 flex 布局，让表格能占据剩余空间
                    'style' => [
                        'height' => '100%',
                        'display' => 'flex',
                        'flexDirection' => 'column',
                    ],
                ])
                ->children($spaceChildren),
        ];

        // 弹窗
        foreach ($this->modals as $name => $config) {
            $children[] = $this->buildModal($name, $config);
        }

        // 抽屉
        foreach ($this->drawers as $name => $config) {
            $children[] = $this->buildDrawer($name, $config);
        }

        return $children;
    }

    protected function buildSearchForm(): Component
    {
        $formItems = [];

        foreach ($this->searchItems as $item) {
            [$label, $name, $component] = $item;
            $component->model("searchForm.{$name}");
            
            $formItems[] = NFormItem::make()
                ->label($label)
                ->children([$component]);
        }

        $formItems[] = NFormItem::make()->children([
            NSpace::make()->children([
                NButton::make()
                    ->type('primary')
                    ->on('click', ['call' => 'search'])
                    ->text('搜索'),
                NButton::make()
                    ->on('click', ['call' => 'resetSearch'])
                    ->text('重置'),
            ]),
        ]);

        return NForm::make()
            ->inline()
            ->props(['labelPlacement' => 'left'])
            ->children($formItems);
    }

    protected function buildToolbar(): ?Component
    {
        $leftComponents = $this->buildToolbarItems($this->toolbarLeft);
        $rightComponents = $this->buildToolbarItems($this->toolbarRight);

        if (empty($leftComponents) && empty($rightComponents)) {
            return null;
        }

        if (empty($rightComponents)) {
            return NSpace::make()->children($leftComponents);
        }

        if (empty($leftComponents)) {
            return NSpace::make()->props(['justify' => 'end'])->children($rightComponents);
        }

        return NSpace::make()
            ->props(['justify' => 'space-between', 'style' => ['width' => '100%']])
            ->children([
                NSpace::make()->children($leftComponents),
                NSpace::make()->children($rightComponents),
            ]);
    }

    protected function buildToolbarItems(array $items): array
    {
        $components = [];
        
        foreach ($items as $item) {
            if (is_string($item)) {
                $component = $this->buildBuiltinToolbarItem($item);
                if ($component) {
                    $components[] = $component;
                }
            } elseif ($item instanceof Component) {
                $components[] = $item;
            }
        }
        
        return $components;
    }

    protected function buildBuiltinToolbarItem(string $type): ?Component
    {
        return match ($type) {
            'columnSelector' => TableColumnSetting::make()->columns('columns'),
            'batchDelete' => NButton::make()
                ->type('error')
                ->props(['disabled' => '{{ selectedRowKeys.length === 0 }}'])
                ->on('click', ['call' => 'handleBatchDelete'])
                ->text('批量删除'),
            'exportCurrent' => NButton::make()
                ->on('click', ['call' => 'handleExportCurrent'])
                ->text('导出当前页'),
            'exportAll' => NButton::make()
                ->on('click', ['call' => 'handleExportAll'])
                ->text('导出全部'),
            'print' => NButton::make()
                ->on('click', ['call' => 'handlePrint'])
                ->text('打印'),
            default => null,
        };
    }

    protected function buildDataTable(): Component
    {
        $tableProps = [
            'loading' => '{{ loading }}',
            'data' => '{{ tableData }}',
            'columns' => '{{ columns.filter(c => c.checked) }}',
            'rowKey' => "{{ row => row.{$this->rowKey} }}",
            'scrollX' => $this->scrollX,
            'flexHeight' => $this->flexHeight,
        ];

        // 当 flexHeight 为 true 时，添加样式让表格占据剩余空间
        if ($this->flexHeight) {
            $tableProps['style'] = [
                'flex' => '1 1 0%',
                'overflow' => 'hidden',
            ];
        }

        // 树形配置
        if ($this->isTree) {
            $tableProps['childrenKey'] = $this->childrenKey;
            $tableProps['defaultExpandAll'] = $this->defaultExpandAll;
            if ($this->indent !== null) {
                $tableProps['indent'] = $this->indent;
            }
            if ($this->expandedRowKeys) {
                $tableProps['expandedRowKeys'] = $this->expandedRowKeys;
            }
        }

        $hasBatchDelete = in_array('batchDelete', array_merge($this->toolbarLeft, $this->toolbarRight));
        if ($hasBatchDelete) {
            $tableProps['checkedRowKeys'] = '{{ selectedRowKeys }}';
        }

        $table = JsonDataTable::make()->props($tableProps);

        if ($hasBatchDelete) {
            $table->on('update:checked-row-keys', ['call' => 'handleSelectionChange', 'args' => ['{{ $event }}']]);
        }

        // 树形展开事件
        if ($this->isTree && $this->expandedRowKeys) {
            $table->on('update:expanded-row-keys', [
                SetAction::make('expandedRowKeys', '{{ $event }}'),
            ]);
        }

        // 表格插槽
        foreach ($this->tableSlots as $column => $config) {
            $table->slot($column, $config['content'], $config['slotProps']);
        }

        return $table;
    }

    /**
     * 构建分页组件
     */
    protected function buildPagination(): Component
    {
        $flex = NFlex::make()
            ->props([
                'justify' => 'end',
                'class' => 'mt-4',
            ]);

        // 数据不超过一页时隐藏分页
        if ($this->hideOnSinglePage) {
            $flex->if('pagination.total > pagination.pageSize');
        }

        $paginationProps = [
            'page' => '{{ pagination.page }}',
            'pageSize' => '{{ pagination.pageSize }}',
            'itemCount' => '{{ pagination.total }}',
            'showSizePicker' => $this->showSizePicker,
            'pageSizes' => $this->pageSizes,
            'showQuickJumper' => $this->showQuickJumper,
        ];

        // 显示总条数：已显示条数/总条数
        if ($this->showItemCount) {
            $paginationProps['prefix'] = "{{ ({ itemCount, page, pageSize }) => `\${Math.min(page * pageSize, itemCount)}/\${itemCount}` }}";
        }

        return $flex->children([
            NPagination::make()
                ->props($paginationProps)
                ->on('update:page', ['call' => 'handlePageChange', 'args' => ['{{ $event }}']])
                ->on('update:page-size', ['call' => 'handlePageSizeChange', 'args' => ['{{ $event }}']]),
        ]);
    }

    protected function buildModal(string $name, array $config): Component
    {
        $content = $config['content'];
        
        // 处理内容
        if (is_string($content)) {
            $children = [$content];
        } elseif ($content instanceof OptForm) {
            // OptForm 转换为数组
            $children = [$content->toArray()];
        } elseif ($content instanceof Component) {
            $children = [$content];
        } else {
            $children = $content;
        }

        return NModal::make()
            ->props([
                'show' => "{{ {$name}Visible }}",
                'title' => $config['title'],
                'style' => ['width' => $config['width']],
                'preset' => 'card',
            ])
            ->on('update:show', ['call' => "handle" . ucfirst($name) . "Close"])
            ->children($children);
    }

    protected function buildDrawer(string $name, array $config): Component
    {
        $content = $config['content'];
        
        // 处理内容
        if (is_string($content)) {
            $drawerChildren = [$content];
        } elseif ($content instanceof OptForm) {
            // OptForm 转换为数组
            $drawerChildren = [$content->toArray()];
        } elseif ($content instanceof Component) {
            $drawerChildren = [$content];
        } else {
            $drawerChildren = $content;
        }

        return NDrawer::make()
            ->props([
                'show' => "{{ {$name}Visible }}",
                'width' => $config['width'],
                'placement' => $config['placement'],
            ])
            ->on('update:show', ['call' => "handle" . ucfirst($name) . "Close"])
            ->children([
                NDrawerContent::make()
                    ->props(['title' => $config['title']])
                    ->children($drawerChildren),
            ]);
    }
}
