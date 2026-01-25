<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Lartrix\Services\PermissionService;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\Select;
use Lartrix\Schema\Components\NaiveUI\SwitchC;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\Tag;
use Lartrix\Schema\Components\NaiveUI\Popconfirm;
use Lartrix\Schema\Components\NaiveUI\Tree;
use Lartrix\Schema\Components\Business\CrudPage;
use Lartrix\Schema\Components\Business\OptForm;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;

class RoleController extends CrudController
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    // ==================== 配置方法 ====================

    protected function getModelClass(): string
    {
        return config('lartrix.models.role', \Lartrix\Models\Role::class);
    }

    protected function getResourceName(): string
    {
        return '角色';
    }

    protected function getTable(): string
    {
        return config('lartrix.tables.roles', 'roles');
    }

    protected function getDefaultOrder(): array
    {
        return ['id', 'asc'];
    }

    protected function getListWith(): array
    {
        return ['permissions'];
    }

    // ==================== 列表重写（非分页） ====================

    /**
     * 获取列表数据（非分页）
     */
    protected function list(Request $request): array
    {
        $query = $this->buildListQuery($request);
        $data = $query->get();

        return success($data->toArray());
    }

    // ==================== 搜索与筛选 ====================

    protected function applySearch(Builder $query, Request $request): void
    {
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('title', 'like', "%{$keyword}%");
            });
        }
    }

    // ==================== 验证规则 ====================

    protected function getStoreRules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:roles',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        return [
            'name' => "string|max:255|unique:roles,name,{$id}",
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    // ==================== 数据处理 ====================

    protected function prepareStoreData(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'guard_name' => 'sanctum',
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
            'is_system' => false,
        ];
    }

    protected function afterStore(mixed $model, array $validated): void
    {
        if (!empty($validated['permissions'])) {
            $this->permissionService->syncRolePermissions($model, $validated['permissions']);
        }
    }

    protected function afterUpdate(mixed $model, array $validated): void
    {
        if (isset($validated['permissions'])) {
            $this->permissionService->syncRolePermissions($model, $validated['permissions']);
        }
    }

    protected function beforeDelete(mixed $model): void
    {
        if ($model->isSystemRole()) {
            throw new \Lartrix\Exceptions\ApiException('不能删除系统内置角色', 40100);
        }
    }

    // ==================== 自定义 action_type ====================

    /**
     * 更新角色权限（action_type=permissions）
     */
    protected function updatePermissions(Request $request, int $id): array
    {
        $model = $this->findOrFail($id);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->permissionService->syncRolePermissions($model, $validated['permissions']);

        return success('权限更新成功', $model->load('permissions')->toArray());
    }

    // ==================== UI Schema ====================

    protected function listUi(): array
    {
        // 角色表单
        $roleForm = OptForm::make('formData')
            ->fields([
                ['角色标识', 'name', Input::make()->props(['placeholder' => '请输入角色标识（英文）', 'disabled' => '{{ !!editingId }}'])],
                ['角色名称', 'title', Input::make()->props(['placeholder' => '请输入角色名称'])],
                ['描述', 'description', Input::make()->props(['type' => 'textarea', 'placeholder' => '请输入角色描述'])],
                ['权限', 'permissions', Tree::make()->props([
                    'data' => $this->getPermissionTree(),
                    'checkable' => true,
                    'selectable' => false,
                    'cascade' => true,
                    'keyField' => 'name',
                    'labelField' => 'title',
                    'childrenField' => 'children',
                    'virtualScroll' => true,
                    'style' => ['maxHeight' => '300px'],
                ]), []],
                ['状态', 'status', SwitchC::make(), true],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        $schema = CrudPage::make('角色管理')
            ->apiPrefix('/roles')
            ->columns($this->getTableColumns())
            ->scrollX(1000)
            ->pagination(false)
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '角色标识/名称', 'clearable' => true])],
                ['状态', 'status', Select::make()->props([
                    'placeholder' => '全部',
                    'clearable' => true,
                    'style' => ['width' => '120px'],
                    'options' => [
                        ['label' => '启用', 'value' => true],
                        ['label' => '禁用', 'value' => false],
                    ],
                ])],
            ])
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::batch([
                            'editingId' => null,
                            'formData.name' => '',
                            'formData.title' => '',
                            'formData.description' => '',
                            'formData.permissions' => [],
                            'formData.status' => true,
                            'formVisible' => true,
                        ]),
                    ])
                    ->text('新增'),
            ])
            ->data([
                'formData' => $roleForm->getDefaultData(),
                'editingId' => null,
                'submitting' => false,
            ])
            ->methods([
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    FetchAction::make('{{ editingId ? "/roles/" + editingId : "/roles" }}')
                        ->method('{{ editingId ? "PUT" : "POST" }}')
                        ->body('{{ formData }}')
                        ->then([
                            CallAction::make('$message.success', ['{{ editingId ? "更新成功" : "创建成功" }}']),
                            SetAction::make('formVisible', false),
                            CallAction::make('loadData'),
                        ])
                        ->catch([
                            CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}']),
                        ])
                        ->finally([
                            SetAction::make('submitting', false),
                        ]),
                ],
            ])
            ->modal('form', '{{ editingId ? "编辑角色" : "新增角色" }}', $roleForm, ['width' => '600px']);

        return success($schema->build());
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
            ['key' => 'status', 'title' => '状态', 'width' => 80, 'slot' => [
                Tag::make()
                    ->props([
                        'type' => "{{ slotData.row.status ? 'success' : 'error' }}",
                        'size' => 'small',
                    ])
                    ->children(["{{ slotData.row.status ? '启用' : '禁用' }}"]),
            ]],
            ['key' => 'is_system', 'title' => '系统角色', 'width' => 100, 'slot' => [
                Tag::make()
                    ->props([
                        'type' => "{{ slotData.row.is_system ? 'warning' : 'default' }}",
                        'size' => 'small',
                    ])
                    ->children(["{{ slotData.row.is_system ? '是' : '否' }}"]),
            ]],
            ['key' => 'actions', 'title' => '操作', 'width' => 150, 'fixed' => 'right', 'slot' => [
                Space::make()->children([
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'primary', 'text' => true])
                        ->on('click', [
                            SetAction::make('editingId', '{{ slotData.row.id }}'),
                            SetAction::make('formData.name', '{{ slotData.row.name }}'),
                            SetAction::make('formData.title', '{{ slotData.row.title || "" }}'),
                            SetAction::make('formData.description', '{{ slotData.row.description || "" }}'),
                            SetAction::make('formData.permissions', '{{ (slotData.row.permissions || []).map(p => p.name) }}'),
                            SetAction::make('formData.status', '{{ slotData.row.status }}'),
                            SetAction::make('formVisible', true),
                        ])
                        ->text('编辑'),
                    Popconfirm::make()
                        ->if('!slotData.row.is_system')
                        ->props([
                            'positiveText' => '确定',
                            'negativeText' => '取消',
                        ])
                        ->on('positive-click',
                            FetchAction::make('/roles/{{ slotData.row.id }}')
                                ->delete()
                                ->then([
                                    CallAction::make('$message.success', ['删除成功']),
                                    CallAction::make('loadData'),
                                ])
                                ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}']),
                                ])
                        )
                        ->slot('trigger', [
                            Button::make()
                                ->size('small')
                                ->props(['type' => 'error', 'text' => true])
                                ->text('删除'),
                        ])
                        ->children(['确定要删除该角色吗？']),
                ]),
            ]],
        ];
    }

    /**
     * 获取权限树
     */
    protected function getPermissionTree(): array
    {
        $permissionModel = config('lartrix.models.permission', \Lartrix\Models\Permission::class);
        return $permissionModel::query()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('sort')
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
