<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Schema\Components\NaiveUI\NCard;
use Lartrix\Schema\Components\NaiveUI\NForm;
use Lartrix\Schema\Components\NaiveUI\NFormItem;
use Lartrix\Schema\Components\NaiveUI\NInput;
use Lartrix\Schema\Components\NaiveUI\NInputNumber;
use Lartrix\Schema\Components\NaiveUI\NSelect;
use Lartrix\Schema\Components\NaiveUI\NSwitch;
use Lartrix\Schema\Components\NaiveUI\NTreeSelect;
use Lartrix\Schema\Components\NaiveUI\NButton;
use Lartrix\Schema\Components\NaiveUI\NSpace;
use Lartrix\Schema\Components\Json\JsonDataTable;
use Lartrix\Schema\Components\NaiveUI\NPopconfirm;
use Lartrix\Schema\Components\NaiveUI\NTag;
use Lartrix\Schema\Components\Custom\SvgIcon;
use function Lartrix\Support\success;
use function Lartrix\Support\error;

class MenuController extends Controller
{
    /**
     * 获取菜单模型类
     */
    protected function getMenuModel(): string
    {
        return config('lartrix.models.menu', \Lartrix\Models\Menu::class);
    }

    /**
     * 当前用户可见菜单（MenuRoute 格式）
     */
    public function index(Request $request): array
    {
        $menuModel = $this->getMenuModel();
        $routes = $menuModel::getRoutesForUser($request->user());
        return success($routes);
    }

    /**
     * 所有菜单（管理用）
     */
    public function all(): array
    {
        $menuModel = $this->getMenuModel();
        $menus = $menuModel::query()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('order')
            ->get();

        // 递归转换 all_children 为 children
        $result = $this->transformMenuChildren($menus->toArray());

        return success($result);
    }

    /**
     * 递归转换菜单子节点字段名
     */
    protected function transformMenuChildren(array $menus): array
    {
        return array_map(function ($menu) {
            if (isset($menu['all_children'])) {
                $menu['children'] = $this->transformMenuChildren($menu['all_children']);
                unset($menu['all_children']);
            }
            return $menu;
        }, $menus);
    }

    /**
     * 创建菜单
     */
    public function store(Request $request): array
    {
        $menuModel = $this->getMenuModel();
        $table = config('lartrix.tables.menus', 'admin_menus');

        $validated = $request->validate([
            'parent_id' => "nullable|integer|exists:{$table},id",
            'name' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'component' => 'nullable|string|max:255',
            'redirect' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer',
            'hide_in_menu' => 'boolean',
            'keep_alive' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'use_json_renderer' => 'boolean',
            'schema_source' => 'nullable|string|max:255',
            'layout_type' => 'nullable|string|in:normal,blank',
            'open_type' => 'nullable|string|in:normal,iframe,newWindow',
            'href' => 'nullable|string|max:255',
            'is_default_after_login' => 'boolean',
            'requires_auth' => 'boolean',
            'active_menu' => 'nullable|string|max:255',
        ]);

        $menu = $menuModel::create($validated);

        return success('创建成功', $menu);
    }

    /**
     * 菜单详情
     */
    public function show(int $id): array
    {
        $menuModel = $this->getMenuModel();
        $menu = $menuModel::with('children')->find($id);

        if (!$menu) {
            error('菜单不存在', null, 40004);
        }

        return success($menu->toArray());
    }

    /**
     * 更新菜单
     */
    public function update(Request $request, int $id): array
    {
        $menuModel = $this->getMenuModel();
        $table = config('lartrix.tables.menus', 'admin_menus');
        $menu = $menuModel::find($id);

        if (!$menu) {
            error('菜单不存在', null, 40004);
        }

        $validated = $request->validate([
            'parent_id' => "nullable|integer|exists:{$table},id",
            'name' => 'string|max:255',
            'path' => 'string|max:255',
            'component' => 'nullable|string|max:255',
            'redirect' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer',
            'hide_in_menu' => 'boolean',
            'keep_alive' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'use_json_renderer' => 'boolean',
            'schema_source' => 'nullable|string|max:255',
            'layout_type' => 'nullable|string|in:normal,blank',
            'open_type' => 'nullable|string|in:normal,iframe,newWindow',
            'href' => 'nullable|string|max:255',
            'is_default_after_login' => 'boolean',
            'requires_auth' => 'boolean',
            'active_menu' => 'nullable|string|max:255',
        ]);

        // 防止设置自己为父级
        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            error('不能将自己设为父级菜单', null, 40022);
        }

        $menu->fill($validated);
        $menu->save();

        return success('更新成功', $menu);
    }

    /**
     * 删除菜单
     */
    public function destroy(int $id): array
    {
        $menuModel = $this->getMenuModel();
        $menu = $menuModel::find($id);

        if (!$menu) {
            error('菜单不存在', null, 40004);
        }

        // 检查是否有子菜单
        if ($menu->children()->exists()) {
            error('请先删除子菜单', null, 40022);
        }

        $menu->delete();

        return success('删除成功');
    }

    /**
     * 菜单排序
     */
    public function sort(Request $request): array
    {
        $menuModel = $this->getMenuModel();
        $table = config('lartrix.tables.menus', 'admin_menus');

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => "required|integer|exists:{$table},id",
            'items.*.order' => 'required|integer',
            'items.*.parent_id' => "nullable|integer|exists:{$table},id",
        ]);

        foreach ($validated['items'] as $item) {
            $menuModel::where('id', $item['id'])->update([
                'order' => $item['order'],
                'parent_id' => $item['parent_id'] ?? null,
            ]);
        }

        return success('排序成功');
    }

    /**
     * 菜单列表页 UI Schema
     */
    public function listUi(): array
    {
        $schema = NCard::make()
            ->props(['title' => '菜单管理'])
            ->data([
                'tableData' => [],
                'loading' => false,
                'expandedKeys' => [],
                'columns' => $this->getTableColumns(),
            ])
            ->methods([
                'loadData' => [
                    ['set' => 'loading', 'value' => true],
                    [
                        'fetch' => '/menus/all',
                        'method' => 'GET',
                        'then' => [
                            ['set' => 'tableData', 'value' => '{{ $response.data || [] }}'],
                        ],
                        'catch' => [
                            ['script' => 'console.error("加载失败:", $error);'],
                        ],
                        'finally' => [
                            ['set' => 'loading', 'value' => false],
                        ],
                    ],
                ],
                'handleAdd' => [
                    ['call' => '$router.push', 'args' => ['/system/menu/add']],
                ],
                'handleEdit' => [
                    ['call' => '$router.push', 'args' => ['/system/menu/edit?id={{ $event.id }}']],
                ],
                'handleAddChild' => [
                    ['call' => '$router.push', 'args' => ['/system/menu/add?parentId={{ $event.id }}']],
                ],
                'handleDelete' => [
                    [
                        'fetch' => '/menus/{{ $event }}',
                        'method' => 'DELETE',
                        'then' => [
                            ['call' => '$message.success', 'args' => ['删除成功']],
                            ['call' => 'loadData'],
                        ],
                        'catch' => [
                            ['call' => '$message.error', 'args' => ['{{ $error.message || "删除失败" }}']],
                        ],
                    ],
                ],
                'expandAll' => [
                    ['script' => "const getAllKeys = (items) => items.reduce((keys, item) => { keys.push(item.id); if (item.children) keys.push(...getAllKeys(item.children)); return keys; }, []); state.expandedKeys = getAllKeys(state.tableData);"],
                ],
                'collapseAll' => [
                    ['set' => 'expandedKeys', 'value' => []],
                ],
            ])
            ->onMounted(['call' => 'loadData'])
            ->children([
                NSpace::make()
                    ->props(['vertical' => true, 'size' => 'large'])
                    ->children([
                        // 操作按钮
                        NSpace::make()->children([
                            NButton::make()
                                ->type('primary')
                                ->on('click', ['call' => 'handleAdd'])
                                ->children([
                                    SvgIcon::make('carbon:add')->props(['class' => 'mr-1']),
                                    '新增菜单',
                                ]),
                            NButton::make()
                                ->on('click', ['call' => 'expandAll'])
                                ->text('展开全部'),
                            NButton::make()
                                ->on('click', ['call' => 'collapseAll'])
                                ->text('折叠全部'),
                        ]),
                        // 数据表格
                        JsonDataTable::make()
                            ->props([
                                'loading' => '{{ loading }}',
                                'data' => '{{ tableData }}',
                                'columns' => '{{ columns }}',
                                'rowKey' => '{{ row => row.id }}',
                                'defaultExpandAll' => true,
                                'expandedRowKeys' => '{{ expandedKeys }}',
                                'scrollX' => 1200,
                            ])
                            ->on('update:expanded-row-keys', ['set' => 'expandedKeys', 'value' => '{{ $event }}'])
                            ->slot('hide_in_menu', [
                                NTag::make()
                                    ->props([
                                        'type' => "{{ slotData.row.hide_in_menu ? 'warning' : 'success' }}",
                                        'size' => 'small',
                                    ])
                                    ->children(["{{ slotData.row.hide_in_menu ? '是' : '否' }}"]),
                            ], 'slotData')
                            ->slot('actions', [
                                NSpace::make()->children([
                                    NButton::make()
                                        ->size('small')
                                        ->props(['type' => 'primary', 'text' => true])
                                        ->on('click', ['call' => 'handleEdit', 'args' => ['{{ slotData.row }}']])
                                        ->text('编辑'),
                                    NButton::make()
                                        ->size('small')
                                        ->props(['type' => 'success', 'text' => true])
                                        ->on('click', ['call' => 'handleAddChild', 'args' => ['{{ slotData.row }}']])
                                        ->text('添加子菜单'),
                                    NPopconfirm::make()
                                        ->on('positive-click', ['call' => 'handleDelete', 'args' => ['{{ slotData.row.id }}']])
                                        ->slot('trigger', [
                                            NButton::make()
                                                ->size('small')
                                                ->props(['type' => 'error', 'text' => true])
                                                ->text('删除'),
                                        ])
                                        ->children(['确定要删除该菜单吗？']),
                                ]),
                            ], 'slotData'),
                    ]),
            ]);

        return success($schema->toArray());
    }

    /**
     * 菜单表单 UI Schema（新增/编辑）
     */
    public function formUi(Request $request): array
    {
        $id = $request->query('id');
        $isEdit = !empty($id);

        // 获取菜单树（用于选择父级）
        $menuTree = $this->getMenuTreeOptions($id);

        $schema = NCard::make()
            ->title($isEdit ? '编辑菜单' : '新增菜单')
            ->children([
                NForm::make()
                    ->props(['labelPlacement' => 'left', 'labelWidth' => 120])
                    ->children([
                        // 基本信息
                        NFormItem::make()->label('父级菜单')->path('parent_id')->children([
                            NTreeSelect::make()->props([
                                'placeholder' => '无（顶级菜单）',
                                'clearable' => true,
                                'options' => $menuTree,
                                'keyField' => 'id',
                                'labelField' => 'title',
                                'childrenField' => 'children',
                            ])->model('parent_id'),
                        ]),
                        NFormItem::make()->label('菜单名称')->path('name')
                            ->props(['required' => true])
                            ->children([
                                NInput::make()->props(['placeholder' => '路由名称（英文）'])->model('name'),
                            ]),
                        NFormItem::make()->label('菜单标题')->path('title')
                            ->props(['required' => true])
                            ->children([
                                NInput::make()->props(['placeholder' => '显示的菜单标题'])->model('title'),
                            ]),
                        NFormItem::make()->label('路由路径')->path('path')
                            ->props(['required' => true])
                            ->children([
                                NInput::make()->props(['placeholder' => '如：/user'])->model('path'),
                            ]),
                        NFormItem::make()->label('组件路径')->path('component')->children([
                            NInput::make()->props(['placeholder' => '如：views/user/index'])->model('component'),
                        ]),
                        NFormItem::make()->label('图标')->path('icon')->children([
                            NInput::make()->props(['placeholder' => '如：mdi:account'])->model('icon'),
                        ]),
                        NFormItem::make()->label('重定向')->path('redirect')->children([
                            NInput::make()->props(['placeholder' => '重定向路径'])->model('redirect'),
                        ]),
                        NFormItem::make()->label('排序')->path('order')->children([
                            NInputNumber::make()->props(['min' => 0])->model('order'),
                        ]),

                        // 布局配置
                        NFormItem::make()->label('布局类型')->path('layout_type')->children([
                            NSelect::make()->props([
                                'options' => [
                                    ['label' => '普通布局', 'value' => 'normal'],
                                    ['label' => '空白布局', 'value' => 'blank'],
                                ],
                            ])->model('layout_type'),
                        ]),
                        NFormItem::make()->label('打开方式')->path('open_type')->children([
                            NSelect::make()->props([
                                'options' => [
                                    ['label' => '正常打开', 'value' => 'normal'],
                                    ['label' => 'iframe 嵌入', 'value' => 'iframe'],
                                    ['label' => '新窗口打开', 'value' => 'newWindow'],
                                ],
                            ])->model('open_type'),
                        ]),
                        NFormItem::make()->label('外链地址')->path('href')
                            ->show("formData.open_type === 'iframe' || formData.open_type === 'newWindow'")
                            ->children([
                                NInput::make()->props(['placeholder' => '外部链接地址'])->model('href'),
                            ]),

                        // JSON 渲染配置
                        NFormItem::make()->label('使用 JSON 渲染')->path('use_json_renderer')->children([
                            NSwitch::make()->model('use_json_renderer'),
                        ]),
                        NFormItem::make()->label('Schema 来源')->path('schema_source')
                            ->show('formData.use_json_renderer')
                            ->children([
                                NInput::make()->props(['placeholder' => 'API 地址或静态文件路径'])->model('schema_source'),
                            ]),

                        // 显示配置
                        NFormItem::make()->label('隐藏菜单')->path('hide_in_menu')->children([
                            NSwitch::make()->model('hide_in_menu'),
                        ]),
                        NFormItem::make()->label('缓存页面')->path('keep_alive')->children([
                            NSwitch::make()->model('keep_alive'),
                        ]),
                        NFormItem::make()->label('需要认证')->path('requires_auth')->children([
                            NSwitch::make()->model('requires_auth'),
                        ]),
                        NFormItem::make()->label('登录后默认页')->path('is_default_after_login')->children([
                            NSwitch::make()->model('is_default_after_login'),
                        ]),

                        // 提交按钮
                        NFormItem::make()->children([
                            NSpace::make()->children([
                                NButton::make()->type('primary')->text('保存')
                                    ->on('click', ['action' => 'submit']),
                                NButton::make()->text('返回')
                                    ->on('click', ['action' => 'back']),
                            ]),
                        ]),
                    ]),
            ]);

        // 编辑时加载菜单数据
        if ($isEdit) {
            $schema->initApi([
                'url' => "menus/{$id}",
                'method' => 'GET',
            ]);
        }

        return success($schema->toArray());
    }

    /**
     * 获取表格列配置
     */
    protected function getTableColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID', 'width' => 80],
            ['key' => 'title', 'title' => '菜单标题'],
            ['key' => 'name', 'title' => '路由名称'],
            ['key' => 'path', 'title' => '路由路径'],
            ['key' => 'icon', 'title' => '图标', 'width' => 100],
            ['key' => 'order', 'title' => '排序', 'width' => 80],
            ['key' => 'hide_in_menu', 'title' => '隐藏', 'width' => 80],
            ['key' => 'actions', 'title' => '操作', 'width' => 200, 'fixed' => 'right'],
        ];
    }

    /**
     * 获取菜单树选项（排除自身及子节点）
     */
    protected function getMenuTreeOptions(?int $excludeId = null): array
    {
        $menuModel = $this->getMenuModel();
        $menus = $menuModel::query()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('order')
            ->get();

        return $menus
            ->map(fn ($m) => $this->formatMenuTreeNode($m, $excludeId))
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * 格式化菜单树节点
     */
    protected function formatMenuTreeNode($menu, ?int $excludeId = null): ?array
    {
        // 排除自身
        if ($excludeId && $menu->id === $excludeId) {
            return null;
        }

        $node = [
            'id' => $menu->id,
            'title' => $menu->title ?: $menu->name,
        ];

        if ($menu->allChildren && $menu->allChildren->count() > 0) {
            $children = $menu->allChildren
                ->map(fn ($c) => $this->formatMenuTreeNode($c, $excludeId))
                ->filter()
                ->values()
                ->toArray();

            if (!empty($children)) {
                $node['children'] = $children;
            }
        }

        return $node;
    }
}
