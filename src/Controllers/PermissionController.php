<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Services\PermissionService;
use Lartrix\Schema\Components\NaiveUI\NCard;
use Lartrix\Schema\Components\NaiveUI\NForm;
use Lartrix\Schema\Components\NaiveUI\NFormItem;
use Lartrix\Schema\Components\NaiveUI\NInput;
use Lartrix\Schema\Components\NaiveUI\NInputNumber;
use Lartrix\Schema\Components\NaiveUI\NSelect;
use Lartrix\Schema\Components\NaiveUI\NTreeSelect;
use Lartrix\Schema\Components\NaiveUI\NButton;
use Lartrix\Schema\Components\NaiveUI\NSpace;
use Lartrix\Schema\Components\Json\JsonDataTable;
use Lartrix\Schema\Components\NaiveUI\NPopconfirm;
use function Lartrix\Support\success;
use function Lartrix\Support\error;

class PermissionController extends Controller
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * 获取权限模型类
     */
    protected function getPermissionModel(): string
    {
        return config('lartrix.models.permission', \Lartrix\Models\Permission::class);
    }

    /**
     * 权限列表
     */
    public function index(Request $request): array
    {
        $permissionModel = $this->getPermissionModel();
        $query = $permissionModel::query();

        // 模块筛选
        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        // 搜索
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('title', 'like', "%{$keyword}%");
            });
        }

        $permissions = $query->orderBy('module')->orderBy('sort')->get();

        return success($permissions->toArray());
    }

    /**
     * 权限树（按模块分组）
     */
    public function tree(): array
    {
        $tree = $this->permissionService->getTreeByModule();
        return success($tree);
    }

    /**
     * 所有权限（树状结构，管理用）
     */
    public function all(): array
    {
        $permissionModel = $this->getPermissionModel();
        $permissions = $permissionModel::query()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('sort')
            ->get();

        // 递归转换 all_children 为 children
        $result = $this->transformPermissionChildren($permissions->toArray());

        return success($result);
    }

    /**
     * 递归转换权限子节点字段名
     */
    protected function transformPermissionChildren(array $permissions): array
    {
        return array_map(function ($permission) {
            if (isset($permission['all_children'])) {
                $permission['children'] = $this->transformPermissionChildren($permission['all_children']);
                unset($permission['all_children']);
            }
            return $permission;
        }, $permissions);
    }

    /**
     * 创建权限
     */
    public function store(Request $request): array
    {
        $permissionModel = $this->getPermissionModel();

        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:permissions,id',
            'name' => 'required|string|max:255|unique:permissions',
            'title' => 'nullable|string|max:255',
            'module' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort' => 'integer',
        ]);

        $permission = $permissionModel::create([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'guard_name' => 'sanctum',
            'module' => $validated['module'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort' => $validated['sort'] ?? 0,
        ]);

        return success('创建成功', $permission);
    }

    /**
     * 权限详情
     */
    public function show(int $id): array
    {
        $permissionModel = $this->getPermissionModel();
        $permission = $permissionModel::with('children')->find($id);

        if (!$permission) {
            error('权限不存在', null, 40004);
        }

        return success($permission->toArray());
    }

    /**
     * 更新权限
     */
    public function update(Request $request, int $id): array
    {
        $permissionModel = $this->getPermissionModel();
        $permission = $permissionModel::find($id);

        if (!$permission) {
            error('权限不存在', null, 40004);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:permissions,id',
            'name' => 'string|max:255|unique:permissions,name,' . $id,
            'title' => 'nullable|string|max:255',
            'module' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort' => 'integer',
        ]);

        // 防止设置自己为父级
        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            error('不能将自己设为父级权限', null, 40022);
        }

        $permission->fill($validated);
        $permission->save();

        return success('更新成功', $permission);
    }

    /**
     * 删除权限
     */
    public function destroy(int $id): array
    {
        $permissionModel = $this->getPermissionModel();
        $permission = $permissionModel::find($id);

        if (!$permission) {
            error('权限不存在', null, 40004);
        }

        // 检查是否有子权限
        if ($permission->children()->exists()) {
            error('请先删除子权限', null, 40022);
        }

        $permission->delete();

        return success('删除成功');
    }

    /**
     * 权限列表页 UI Schema
     */
    public function listUi(): array
    {
        $schema = NCard::make()
            ->props(['title' => '权限管理'])
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
                        'fetch' => '/permissions/all',
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
                    ['call' => '$router.push', 'args' => ['/system/permission/add']],
                ],
                'handleAddChild' => [
                    ['call' => '$router.push', 'args' => ['/system/permission/add?parentId={{ $event.id }}']],
                ],
                'handleEdit' => [
                    ['call' => '$router.push', 'args' => ['/system/permission/edit?id={{ $event.id }}']],
                ],
                'handleDelete' => [
                    [
                        'fetch' => '/permissions/{{ $event }}',
                        'method' => 'DELETE',
                        'then' => [
                            ['call' => '$message.success', 'args' => ['删除成功']],
                            ['call' => 'loadData'],
                        ],
                        'catch' => [
                            ['script' => 'console.error("删除失败:", $error);'],
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
                                ->text('新增权限'),
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
                                'scrollX' => 1000,
                            ])
                            ->on('update:expanded-row-keys', ['set' => 'expandedKeys', 'value' => '{{ $event }}'])
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
                                        ->text('添加子权限'),
                                    NPopconfirm::make()
                                        ->on('positive-click', ['call' => 'handleDelete', 'args' => ['{{ slotData.row.id }}']])
                                        ->slot('trigger', [
                                            NButton::make()
                                                ->size('small')
                                                ->props(['type' => 'error', 'text' => true])
                                                ->text('删除'),
                                        ])
                                        ->children(['确定要删除该权限吗？']),
                                ]),
                            ], 'slotData'),
                    ]),
            ]);

        return success($schema->toArray());
    }

    /**
     * 权限表单 UI Schema（新增/编辑）
     */
    public function formUi(Request $request): array
    {
        $id = $request->query('id');
        $isEdit = !empty($id);

        // 获取权限树（用于选择父级）
        $permissionTree = $this->getPermissionTreeOptions($id);

        $schema = NCard::make()
            ->title($isEdit ? '编辑权限' : '新增权限')
            ->children([
                NForm::make()
                    ->props(['labelPlacement' => 'left', 'labelWidth' => 100])
                    ->children([
                        NFormItem::make()->label('父级权限')->path('parent_id')->children([
                            NTreeSelect::make()->props([
                                'placeholder' => '无（顶级权限）',
                                'clearable' => true,
                                'options' => $permissionTree,
                                'keyField' => 'id',
                                'labelField' => 'title',
                                'childrenField' => 'children',
                            ])->model('parent_id'),
                        ]),
                        NFormItem::make()->label('权限标识')->path('name')
                            ->props(['required' => true])
                            ->children([
                                NInput::make()
                                    ->props(['placeholder' => '如：user.create'])
                                    ->model('name'),
                            ]),
                        NFormItem::make()->label('权限名称')->path('title')->children([
                            NInput::make()->props(['placeholder' => '请输入权限名称'])->model('title'),
                        ]),
                        NFormItem::make()->label('所属模块')->path('module')->children([
                            NInput::make()->props(['placeholder' => '请输入模块名称'])->model('module'),
                        ]),
                        NFormItem::make()->label('描述')->path('description')->children([
                            NInput::make()->props([
                                'type' => 'textarea',
                                'placeholder' => '请输入权限描述',
                            ])->model('description'),
                        ]),
                        NFormItem::make()->label('排序')->path('sort')->children([
                            NInputNumber::make()->props(['min' => 0])->model('sort'),
                        ]),
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

        // 编辑时加载权限数据
        if ($isEdit) {
            $schema->initApi([
                'url' => "permissions/{$id}",
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
            ['key' => 'name', 'title' => '权限标识'],
            ['key' => 'title', 'title' => '权限名称'],
            ['key' => 'module', 'title' => '所属模块'],
            ['key' => 'description', 'title' => '描述'],
            ['key' => 'sort', 'title' => '排序', 'width' => 80],
            ['key' => 'actions', 'title' => '操作', 'width' => 150, 'fixed' => 'right'],
        ];
    }

    /**
     * 获取模块选项
     */
    protected function getModuleOptions(): array
    {
        $permissionModel = $this->getPermissionModel();
        return $permissionModel::query()
            ->whereNotNull('module')
            ->distinct()
            ->pluck('module')
            ->map(fn ($m) => ['label' => $m, 'value' => $m])
            ->toArray();
    }

    /**
     * 获取权限树选项（排除自身及子节点）
     */
    protected function getPermissionTreeOptions(?int $excludeId = null): array
    {
        $permissionModel = $this->getPermissionModel();
        $query = $permissionModel::query()->whereNull('parent_id')->with('allChildren')->orderBy('sort');

        $permissions = $query->get();

        return $permissions
            ->map(fn ($p) => $this->formatPermissionTreeNode($p, $excludeId))
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * 格式化权限树节点
     */
    protected function formatPermissionTreeNode($permission, ?int $excludeId = null): ?array
    {
        // 排除自身
        if ($excludeId && $permission->id === $excludeId) {
            return null;
        }

        $node = [
            'id' => $permission->id,
            'title' => $permission->title ?: $permission->name,
        ];

        if ($permission->allChildren && $permission->allChildren->count() > 0) {
            $children = $permission->allChildren
                ->map(fn ($c) => $this->formatPermissionTreeNode($c, $excludeId))
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
