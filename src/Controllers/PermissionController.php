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
use Lartrix\Schema\Actions\IfAction;

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
        return __t('column.permissions');
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
            throw new \Lartrix\Exceptions\ApiException(__t('permission.cannot_parent_self'), 40022);
        }

        return $validated;
    }

    protected function beforeDelete(mixed $model): void
    {
        if ($model->children()->exists()) {
            throw new \Lartrix\Exceptions\ApiException(__t('permission.delete_children_first'), 40022);
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
                [__t('form.parent_id'), 'parent_id', TreeSelect::make()->props([
                    'placeholder' => __t('placeholder.parent'),
                    'clearable' => true,
                    'options' => '{{ permissionTreeOptions }}',
                    'keyField' => 'id',
                    'labelField' => 'title',
                    'childrenField' => 'children',
                ])],
                [__t('column.permission_identifier'), 'name', Input::make()->props(['placeholder' => __t('placeholder.perm_name')])],
                [__t('column.permission_title'), 'title', Input::make()->props(['placeholder' => __t('placeholder.perm_title')])],
                [__t('column.module'), 'module', Input::make()->props(['placeholder' => __t('placeholder.module')])],
                [__t('column.description'), 'description', Input::make()->props(['type' => 'textarea', 'placeholder' => __t('placeholder.permission_description')])],
                [__t('column.sort'), 'sort', InputNumber::make()->props(['min' => 0]), 0],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('button.confirm')),
            ]);

        $schema = CrudPage::make(__t('title.permission_manage'))
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
                    ->text(__t('button.create')),
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
                    IfAction::make('editingId')
                        ->then(
                            FetchAction::make('{{ "/permissions/" + editingId }}')
                                ->put()
                                ->body('{{ formData }}')
                                ->then([
                                    CallAction::make('$message.success', [__t('crud.updated')]),
                                    SetAction::make('formVisible', false),
                                    CallAction::make('loadData'),
                                ])
                                ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "' . __t('crud.operation_failed') . '" }}']),
                                ])
                                ->finally([
                                    SetAction::make('submitting', false),
                                ])
                        )
                        ->else(
                            FetchAction::make('/permissions')
                                ->post()
                                ->body('{{ formData }}')
                                ->then([
                                    CallAction::make('$message.success', [__t('crud.created')]),
                                    SetAction::make('formVisible', false),
                                    CallAction::make('loadData'),
                                ])
                                ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "' . __t('crud.operation_failed') . '" }}']),
                                ])
                                ->finally([
                                    SetAction::make('submitting', false),
                                ])
                        ),
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
            ->modal('form', '{{ editingId ? "' . __t('title.edit_permission') . '" : "' . __t('title.create_permission') . '" }}', $permissionForm, ['width' => '500px']);

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
            ['key' => 'name', 'title' => __t('column.permission_identifier')],
            ['key' => 'title', 'title' => __t('column.permission_title')],
            ['key' => 'module', 'title' => __t('column.module')],
            ['key' => 'description', 'title' => __t('column.description')],
            ['key' => 'sort', 'title' => __t('column.sort'), 'width' => 80],
            ['key' => 'actions', 'title' => __t('column.actions'), 'width' => 200, 'fixed' => 'right', 'slot' => [
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
                        ->text(__t('button.edit')),
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'success', 'text' => true])
                        ->on('click', ['call' => 'handleAddChild', 'args' => ['{{ slotData.row }}']])
                        ->text(__t('button.add_child_perm')),
                    Popconfirm::make()
                        ->props([
                            'positiveText' => __t('button.confirm'),
                            'negativeText' => __t('button.cancel'),
                        ])
                        ->on('positive-click',
                            FetchAction::make('/permissions/{{ slotData.row.id }}')
                                ->delete()
                                ->then([
                                    CallAction::make('$message.success', [__t('crud.deleted')]),
                                    CallAction::make('loadData'),
                                ])
                                ->catch([
                        CallAction::make('$message.error', ['{{ $error.message || "' . __t('crud.delete_failed') . '" }}']),
                                ])
                        )
                        ->slot('trigger', [
                            Button::make()
                                ->size('small')
                                ->props(['type' => 'error', 'text' => true])
                                ->text(__t('button.delete')),
                        ])
                        ->children([__t('confirm.delete_permission')]),
                ]),
            ]],
        ];
    }
}
