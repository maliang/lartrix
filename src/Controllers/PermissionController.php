<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Lartrix\Services\PermissionService;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\InputNumber;
use Lartrix\Schema\Components\NaiveUI\TreeSelect;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\Popconfirm;
use Lartrix\Schema\Components\Business\CrudPage;
use Lartrix\Schema\Components\Business\OptForm;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;

class PermissionController extends CrudController
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    // ==================== 配置方法 ====================

    protected function getModelClass(): string
    {
        return config('lartrix.models.permission', \Lartrix\Models\Permission::class);
    }

    protected function getResourceName(): string
    {
        return '权限';
    }

    protected function getTable(): string
    {
        return config('lartrix.tables.permissions', 'permissions');
    }

    protected function getDefaultOrder(): array
    {
        return ['sort', 'asc'];
    }

    // ==================== 路由方法重写 ====================

    public function index(Request $request): mixed
    {
        $actionType = $request->input('action_type', 'list');

        return match ($actionType) {
            'tree' => $this->tree(),
            'all' => $this->all(),
            'list_ui' => $this->listUi(),
            'form_ui' => $this->formUi($request),
            default => $this->list($request),
        };
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

    protected function applyFilters(Builder $query, Request $request): void
    {
        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }
    }

    // ==================== 验证规则 ====================

    protected function getStoreRules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:permissions,id',
            'name' => 'required|string|max:255|unique:permissions',
            'title' => 'nullable|string|max:255',
            'module' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort' => 'integer',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:permissions,id',
            'name' => "string|max:255|unique:permissions,name,{$id}",
            'title' => 'nullable|string|max:255',
            'module' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort' => 'integer',
        ];
    }

    // ==================== 数据处理 ====================

    protected function prepareStoreData(array $validated): array
    {
        return [
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'guard_name' => 'sanctum',
            'module' => $validated['module'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort' => $validated['sort'] ?? 0,
        ];
    }

    protected function validateUpdate(Request $request, int $id): array
    {
        $validated = parent::validateUpdate($request, $id);

        // 防止设置自己为父级
        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            throw new \Lartrix\Exceptions\ApiException('不能将自己设为父级权限', 40022);
        }

        return $validated;
    }

    protected function beforeDelete(mixed $model): void
    {
        if ($model->children()->exists()) {
            throw new \Lartrix\Exceptions\ApiException('请先删除子权限', 40022);
        }
    }

    // ==================== 自定义方法 ====================

    /**
     * 权限树（按模块分组）
     */
    protected function tree(): array
    {
        $tree = $this->permissionService->getTreeByModule();
        return success($tree);
    }

    /**
     * 所有权限（树状结构，管理用）
     */
    protected function all(): array
    {
        $modelClass = $this->getModelClass();
        $permissions = $modelClass::query()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('sort')
            ->get();

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

    // ==================== UI Schema ====================

    protected function listUi(): array
    {
        // 权限表单
        $permissionForm = OptForm::make('formData')
            ->fields([
                ['父级权限', 'parent_id', TreeSelect::make()->props([
                    'placeholder' => '无（顶级权限）',
                    'clearable' => true,
                    'options' => '{{ permissionTreeOptions }}',
                    'keyField' => 'id',
                    'labelField' => 'title',
                    'childrenField' => 'children',
                ])],
                ['权限标识', 'name', Input::make()->props(['placeholder' => '如：user.create'])],
                ['权限名称', 'title', Input::make()->props(['placeholder' => '请输入权限名称'])],
                ['所属模块', 'module', Input::make()->props(['placeholder' => '请输入模块名称'])],
                ['描述', 'description', Input::make()->props(['type' => 'textarea', 'placeholder' => '请输入权限描述'])],
                ['排序', 'sort', InputNumber::make()->props(['min' => 0]), 0],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        $schema = CrudPage::make('权限管理')
            ->apiPrefix('/permissions')
            ->apiParams(['action_type' => 'all'])
            ->columns($this->getTableColumns())
            ->scrollX(1000)
            ->pagination(false)
            ->tree()
            ->toolbarLeft([
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::batch([
                            'editingId' => null,
                            'formData.parent_id' => null,
                            'formData.name' => '',
                            'formData.title' => '',
                            'formData.module' => '',
                            'formData.description' => '',
                            'formData.sort' => 0,
                            'formVisible' => true,
                        ]),
                        CallAction::make('loadPermissionTree'),
                    ])
                    ->text('新增'),
                'expandAll',
                'collapseAll',
            ])
            ->data([
                'formData' => $permissionForm->getDefaultData(),
                'editingId' => null,
                'submitting' => false,
                'permissionTreeOptions' => [],
            ])
            ->methods([
                'loadPermissionTree' => [
                    FetchAction::make('/permissions?action_type=all')
                        ->get()
                        ->then([
                            SetAction::make('permissionTreeOptions', '{{ $response.data || [] }}'),
                        ]),
                ],
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    FetchAction::make('{{ editingId ? "/permissions/" + editingId : "/permissions" }}')
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
                'handleAddChild' => [
                    SetAction::batch([
                        'editingId' => null,
                        'formData.parent_id' => '{{ $event.id }}',
                        'formData.name' => '',
                        'formData.title' => '',
                        'formData.module' => '{{ $event.module || "" }}',
                        'formData.description' => '',
                        'formData.sort' => 0,
                        'formVisible' => true,
                    ]),
                    CallAction::make('loadPermissionTree'),
                ],
            ])
            ->modal('form', '{{ editingId ? "编辑权限" : "新增权限" }}', $permissionForm, ['width' => '500px']);

        return success($schema->build());
    }

    protected function formUi(): array
    {
        // 保留旧的 formUi 以兼容
        return $this->listUi();
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
            ['key' => 'actions', 'title' => '操作', 'width' => 200, 'fixed' => 'right', 'slot' => [
                Space::make()->children([
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'primary', 'text' => true])
                        ->on('click', [
                            SetAction::make('editingId', '{{ slotData.row.id }}'),
                            SetAction::make('formData.parent_id', '{{ slotData.row.parent_id }}'),
                            SetAction::make('formData.name', '{{ slotData.row.name }}'),
                            SetAction::make('formData.title', '{{ slotData.row.title || "" }}'),
                            SetAction::make('formData.module', '{{ slotData.row.module || "" }}'),
                            SetAction::make('formData.description', '{{ slotData.row.description || "" }}'),
                            SetAction::make('formData.sort', '{{ slotData.row.sort || 0 }}'),
                            SetAction::make('formVisible', true),
                            CallAction::make('loadPermissionTree'),
                        ])
                        ->text('编辑'),
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'success', 'text' => true])
                        ->on('click', ['call' => 'handleAddChild', 'args' => ['{{ slotData.row }}']])
                        ->text('添加子权限'),
                    Popconfirm::make()
                        ->props([
                            'positiveText' => '确定',
                            'negativeText' => '取消',
                        ])
                        ->on('positive-click',
                            FetchAction::make('/permissions/{{ slotData.row.id }}')
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
                        ->children(['确定要删除该权限吗？']),
                ]),
            ]],
        ];
    }
}
