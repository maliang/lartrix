<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Services\PermissionService;
use Lartrix\Schema\Components\NaiveUI\NCard;
use Lartrix\Schema\Components\NaiveUI\NForm;
use Lartrix\Schema\Components\NaiveUI\NFormItem;
use Lartrix\Schema\Components\NaiveUI\NInput;
use Lartrix\Schema\Components\NaiveUI\NSelect;
use Lartrix\Schema\Components\NaiveUI\NSwitch;
use Lartrix\Schema\Components\NaiveUI\NButton;
use Lartrix\Schema\Components\NaiveUI\NSpace;
use Lartrix\Schema\Components\Json\JsonDataTable;
use Lartrix\Schema\Components\NaiveUI\NTag;
use Lartrix\Schema\Components\NaiveUI\NPopconfirm;
use Lartrix\Schema\Components\NaiveUI\NTree;
use function Lartrix\Support\success;
use function Lartrix\Support\error;

class RoleController extends Controller
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * 获取角色模型类
     */
    protected function getRoleModel(): string
    {
        return config('lartrix.models.role', \Lartrix\Models\Role::class);
    }

    /**
     * 获取权限模型类
     */
    protected function getPermissionModel(): string
    {
        return config('lartrix.models.permission', \Lartrix\Models\Permission::class);
    }

    /**
     * 角色列表
     */
    public function index(Request $request): array
    {
        $roleModel = $this->getRoleModel();
        $query = $roleModel::query();

        // 搜索
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('title', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        }

        $roles = $query->with('permissions')->orderBy('id')->get();

        return success($roles->toArray());
    }

    /**
     * 创建角色
     */
    public function store(Request $request): array
    {
        $roleModel = $this->getRoleModel();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = $roleModel::create([
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'guard_name' => 'sanctum',
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
            'is_system' => false,
        ]);

        // 分配权限
        if (!empty($validated['permissions'])) {
            $this->permissionService->syncRolePermissions($role, $validated['permissions']);
        }

        return success('创建成功', $role->load('permissions'));
    }

    /**
     * 角色详情
     */
    public function show(int $id): array
    {
        $roleModel = $this->getRoleModel();
        $role = $roleModel::with('permissions')->find($id);

        if (!$role) {
            error('角色不存在', null, 40004);
        }

        return success($role->toArray());
    }

    /**
     * 更新角色
     */
    public function update(Request $request, int $id): array
    {
        $roleModel = $this->getRoleModel();
        $role = $roleModel::find($id);

        if (!$role) {
            error('角色不存在', null, 40004);
        }

        $validated = $request->validate([
            'name' => 'string|max:255|unique:roles,name,' . $id,
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $role->fill($validated);
        $role->save();

        return success('更新成功', $role->load('permissions'));
    }

    /**
     * 删除角色
     */
    public function destroy(int $id): array
    {
        $roleModel = $this->getRoleModel();
        $role = $roleModel::find($id);

        if (!$role) {
            error('角色不存在', null, 40004);
        }

        // 系统角色保护
        if ($role->isSystemRole()) {
            error('不能删除系统内置角色', null, 40100);
        }

        $role->delete();

        return success('删除成功');
    }

    /**
     * 更新角色权限（批量分配）
     */
    public function updatePermissions(Request $request, int $id): array
    {
        $roleModel = $this->getRoleModel();
        $role = $roleModel::find($id);

        if (!$role) {
            error('角色不存在', null, 40004);
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->permissionService->syncRolePermissions($role, $validated['permissions']);

        return success('权限更新成功', $role->load('permissions'));
    }

    /**
     * 角色列表页 UI Schema
     */
    public function listUi(): array
    {
        $schema = NCard::make()
            ->props(['title' => '角色管理'])
            ->data([
                'searchForm' => [
                    'keyword' => '',
                    'status' => null,
                ],
                'tableData' => [],
                'loading' => false,
                'columns' => $this->getTableColumns(),
                'statusOptions' => [
                    ['label' => '启用', 'value' => true],
                    ['label' => '禁用', 'value' => false],
                ],
            ])
            ->methods([
                'loadData' => [
                    ['set' => 'loading', 'value' => true],
                    [
                        'fetch' => '/roles',
                        'method' => 'GET',
                        'params' => [
                            'keyword' => '{{ searchForm.keyword }}',
                            'status' => '{{ searchForm.status }}',
                        ],
                        'then' => [
                            ['set' => 'tableData', 'value' => '{{ $response.data || [] }}'],
                        ],
                        'catch' => [
                            ['call' => '$message.error', 'args' => ['{{ $error.message || "加载数据失败" }}']],
                        ],
                        'finally' => [
                            ['set' => 'loading', 'value' => false],
                        ],
                    ],
                ],
                'search' => [
                    ['call' => 'loadData'],
                ],
                'resetSearch' => [
                    ['set' => 'searchForm.keyword', 'value' => ''],
                    ['set' => 'searchForm.status', 'value' => null],
                    ['call' => 'loadData'],
                ],
                'handleAdd' => [
                    ['call' => '$router.push', 'args' => ['/system/role/add']],
                ],
                'handleEdit' => [
                    ['call' => '$router.push', 'args' => ['/system/role/edit?id={{ $event.id }}']],
                ],
                'handlePermissions' => [
                    ['call' => '$router.push', 'args' => ['/system/role/permissions?id={{ $event.id }}']],
                ],
                'handleDelete' => [
                    [
                        'fetch' => '/roles/{{ $event }}',
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
            ])
            ->onMounted(['call' => 'loadData'])
            ->children([
                NSpace::make()
                    ->props(['vertical' => true, 'size' => 'large'])
                    ->children([
                        // 搜索表单
                        NForm::make()
                            ->inline()
                            ->props(['labelPlacement' => 'left'])
                            ->children([
                                NFormItem::make()->label('关键词')->children([
                                    NInput::make()
                                        ->props(['placeholder' => '角色名称/标题', 'clearable' => true])
                                        ->model('searchForm.keyword'),
                                ]),
                                NFormItem::make()->label('状态')->children([
                                    NSelect::make()
                                        ->props([
                                            'placeholder' => '全部',
                                            'clearable' => true,
                                            'options' => '{{ statusOptions }}',
                                            'style' => ['width' => '120px'],
                                        ])
                                        ->model('searchForm.status'),
                                ]),
                                NFormItem::make()->children([
                                    NSpace::make()->children([
                                        NButton::make()
                                            ->type('primary')
                                            ->on('click', ['call' => 'search'])
                                            ->text('搜索'),
                                        NButton::make()
                                            ->on('click', ['call' => 'resetSearch'])
                                            ->text('重置'),
                                    ]),
                                ]),
                            ]),
                        // 操作按钮
                        NSpace::make()->children([
                            NButton::make()
                                ->type('primary')
                                ->on('click', ['call' => 'handleAdd'])
                                ->text('新增角色'),
                        ]),
                        // 数据表格
                        JsonDataTable::make()
                            ->props([
                                'loading' => '{{ loading }}',
                                'data' => '{{ tableData }}',
                                'columns' => '{{ columns }}',
                                'rowKey' => '{{ row => row.id }}',
                                'scrollX' => 1000,
                            ])
                            ->slot('status', [
                                NTag::make()
                                    ->props([
                                        'type' => "{{ slotData.row.status ? 'success' : 'error' }}",
                                        'size' => 'small',
                                    ])
                                    ->children(["{{ slotData.row.status ? '启用' : '禁用' }}"]),
                            ], 'slotData')
                            ->slot('is_system', [
                                NTag::make()
                                    ->props([
                                        'type' => "{{ slotData.row.is_system ? 'warning' : 'default' }}",
                                        'size' => 'small',
                                    ])
                                    ->children(["{{ slotData.row.is_system ? '是' : '否' }}"]),
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
                                        ->props(['type' => 'warning', 'text' => true])
                                        ->on('click', ['call' => 'handlePermissions', 'args' => ['{{ slotData.row }}']])
                                        ->text('权限'),
                                    NPopconfirm::make()
                                        ->if('!slotData.row.is_system')
                                        ->on('positive-click', ['call' => 'handleDelete', 'args' => ['{{ slotData.row.id }}']])
                                        ->slot('trigger', [
                                            NButton::make()
                                                ->size('small')
                                                ->props(['type' => 'error', 'text' => true])
                                                ->text('删除'),
                                        ])
                                        ->children(['确定要删除该角色吗？']),
                                ]),
                            ], 'slotData'),
                    ]),
            ]);

        return success($schema->toArray());
    }

    /**
     * 角色表单 UI Schema（新增/编辑）
     */
    public function formUi(Request $request): array
    {
        $id = $request->query('id');
        $isEdit = !empty($id);

        // 获取权限树
        $permissionTree = $this->getPermissionTree();

        $schema = NCard::make()
            ->title($isEdit ? '编辑角色' : '新增角色')
            ->children([
                NForm::make()
                    ->props(['labelPlacement' => 'left', 'labelWidth' => 100])
                    ->children([
                        NFormItem::make()->label('角色标识')->path('name')
                            ->props(['required' => true])
                            ->children([
                                NInput::make()
                                    ->props(['placeholder' => '请输入角色标识（英文）', 'disabled' => $isEdit])
                                    ->model('name'),
                            ]),
                        NFormItem::make()->label('角色名称')->path('title')->children([
                            NInput::make()->props(['placeholder' => '请输入角色名称'])->model('title'),
                        ]),
                        NFormItem::make()->label('描述')->path('description')->children([
                            NInput::make()->props([
                                'type' => 'textarea',
                                'placeholder' => '请输入角色描述',
                            ])->model('description'),
                        ]),
                        NFormItem::make()->label('权限')->path('permissions')->children([
                            NTree::make()->props([
                                'data' => $permissionTree,
                                'checkable' => true,
                                'selectable' => false,
                                'cascade' => true,
                                'keyField' => 'name',
                                'labelField' => 'title',
                                'childrenField' => 'children',
                            ])->model('permissions'),
                        ]),
                        NFormItem::make()->label('状态')->path('status')->children([
                            NSwitch::make()->model('status'),
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

        // 编辑时加载角色数据
        if ($isEdit) {
            $schema->initApi([
                'url' => "roles/{$id}",
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
            ['key' => 'name', 'title' => '角色标识'],
            ['key' => 'title', 'title' => '角色名称'],
            ['key' => 'description', 'title' => '描述'],
            ['key' => 'status', 'title' => '状态', 'width' => 80],
            ['key' => 'is_system', 'title' => '系统角色', 'width' => 100],
            ['key' => 'actions', 'title' => '操作', 'width' => 180, 'fixed' => 'right'],
        ];
    }

    /**
     * 获取权限树
     */
    protected function getPermissionTree(): array
    {
        $permissionModel = $this->getPermissionModel();
        return $permissionModel::query()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('order')
            ->get()
            ->map(fn ($p) => $this->formatPermissionNode($p))
            ->toArray();
    }

    /**
     * 格式化权限节点
     */
    protected function formatPermissionNode($permission): array
    {
        $node = [
            'name' => $permission->name,
            'title' => $permission->title ?: $permission->name,
        ];

        if ($permission->allChildren && $permission->allChildren->count() > 0) {
            $node['children'] = $permission->allChildren
                ->map(fn ($c) => $this->formatPermissionNode($c))
                ->toArray();
        }

        return $node;
    }
}
