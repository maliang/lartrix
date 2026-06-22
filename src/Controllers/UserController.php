<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Lartrix\Services\AuthService;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\Select;
use Lartrix\Schema\Components\NaiveUI\SwitchC;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\Tag;
use Lartrix\Schema\Components\NaiveUI\Popconfirm;
use Lartrix\Schema\Components\Business\CrudPage;
use Lartrix\Schema\Components\Business\OptForm;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;
use Lartrix\Schema\Actions\IfAction;

class UserController extends CrudController
{
    public function __construct(
        protected AuthService $authService
    ) {}

    // ==================== 配置方法 ====================

    protected function getModelClass(): string
    {
        return config('lartrix.models.user', \Lartrix\Models\AdminUser::class);
    }

    protected function getResourceName(): string
    {
        return __t('user.resource_name');
    }

    protected function getTable(): string
    {
        return config('lartrix.tables.users', 'admin_users');
    }

    protected function getListWith(): array
    {
        return ['roles'];
    }

    protected function getExportFilenamePrefix(): string
    {
        return __t('user.export_prefix');
    }

    protected function getExportColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'username', 'title' => __t('column.username')],
            ['key' => 'nickname', 'title' => __t('column.nickname')],
            ['key' => 'email', 'title' => __t('column.email')],
            ['key' => 'phone', 'title' => __t('column.phone')],
            ['key' => 'roles', 'title' => __t('column.roles')],
            ['key' => 'status', 'title' => __t('column.status')],
            ['key' => 'last_login_time', 'title' => __t('column.last_login_time')],
            ['key' => 'created_at', 'title' => __t('column.created_at')],
        ];
    }

    // ==================== 搜索与筛选 ====================

    protected function applySearch(Builder $query, Request $request): void
    {
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                    ->orWhere('nickname', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }
    }

    // ==================== 验证规则 ====================

    protected function getStoreRules(): array
    {
        $table = $this->getTable();
        return [
            'username' => "required|string|max:20|unique:{$table}",
            'password' => 'required|string|min:6',
            'nickname' => 'nullable|string|max:20',
            'avatar' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:10',
            'remark' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        $table = $this->getTable();
        return [
            'username' => "string|max:20|unique:{$table},username,{$id}",
            'nickname' => 'nullable|string|max:20',
            'avatar' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'remark' => 'nullable|string|max:255',
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
        ];
    }

    // ==================== 数据处理 ====================

    protected function prepareStoreData(array $validated): array
    {
        return [
            'username' => $validated['username'],
            'password' => $validated['password'],
            'nickname' => ($validated['nickname'] ?? null) ?: null,
            'avatar' => ($validated['avatar'] ?? null) ?: null,
            'email' => ($validated['email'] ?? null) ?: null,
            'phone' => ($validated['phone'] ?? null) ?: null,
            'status' => $validated['status'] ?? '1',
            'remark' => ($validated['remark'] ?? null) ?: null,
        ];
    }

    protected function afterStore(mixed $model, array $validated): void
    {
        if (!empty($validated['roles'])) {
            $model->syncRoles($validated['roles']);
        }
    }

    protected function afterUpdate(mixed $model, array $validated): void
    {
        if (isset($validated['roles'])) {
            $model->syncRoles($validated['roles']);
        }
    }

    // ==================== 状态与删除回调 ====================

    /**
     * 更新状态（重写以支持字符串类型的 status）
     */
    protected function updateStatus(Request $request, int $id): array
    {
        $model = $this->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:0,1',
        ]);

        $model->status = $validated['status'];
        $model->save();

        $this->afterStatusUpdate($model, $validated['status'] === '1');

        return success(__t('crud.status_updated'), ['status' => $model->status]);
    }

    protected function afterStatusUpdate(mixed $model, bool $status): void
    {
        // 禁用时撤销所有 Token
        if (!$status) {
            $this->authService->revokeAllTokens($model);
        }
    }

    protected function beforeDelete(mixed $model): void
    {
        $this->authService->revokeAllTokens($model);
    }

    // ==================== 自定义 action_type ====================

    /**
     * 重置密码（action_type=reset_password）
     */
    protected function updateResetPassword(Request $request, int $id): array
    {
        $model = $this->findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $model->password = $validated['password'];
        $model->save();

        // 撤销所有 Token，强制重新登录
        $this->authService->revokeAllTokens($model);

        return success(__t('auth.password_reset_ok'));
    }

    // ==================== UI Schema ====================

    protected function listUi(): array
    {
        // 用户表单
        $userForm = OptForm::make('formData')
            ->fields([
                [__t('column.username'), 'username', Input::make()->props(['placeholder' => __t('placeholder.username'), 'disabled' => '{{ !!editingId }}'])],
                [__t('column.nickname'), 'nickname', Input::make()->props(['placeholder' => __t('placeholder.nickname')])],
                [__t('column.email'), 'email', Input::make()->props(['placeholder' => __t('placeholder.email')])],
                [__t('column.phone'), 'phone', Input::make()->props(['placeholder' => __t('placeholder.phone')])],
                [__t('form.password'), 'password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => __t('placeholder.password')]), '', '!editingId'],
                [__t('column.roles'), 'roles', Select::make()->props(['multiple' => true, 'placeholder' => __t('placeholder.select_roles'), 'options' => '{{ roleOptions }}']), []],
                [__t('form.remark'), 'remark', Input::make()->props(['type' => 'textarea', 'placeholder' => __t('placeholder.remark')])],
                [__t('column.status'), 'status', SwitchC::make()->props(['checkedValue' => '1', 'uncheckedValue' => '0']), '1'],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('button.confirm')),
            ]);

        // 重置密码表单
        $resetPwdForm = OptForm::make()
            ->fields([
                [__t('form.new_password'), 'newPassword', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => __t('placeholder.new_pwd')])],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('resetPwdVisible', false))->text(__t('button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ resetPwdSubmitting }}'])->on('click', [
                    SetAction::make('resetPwdSubmitting', true),
                    FetchAction::make('/users/{{ resetPwdUserId }}')
                        ->put()
                        ->body(['action_type' => 'reset_password', 'password' => '{{ newPassword }}'])
                        ->then([
                                    CallAction::make('$message.success', [__t('auth.password_reset_ok')]),
                            SetAction::make('resetPwdVisible', false),
                        ])
                        ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "' . __t('crud.operation_failed') . '" }}']),
                        ])
                        ->finally([
                            SetAction::make('resetPwdSubmitting', false),
                        ]),
                ])->text(__t('button.confirm')),
            ]);

        $schema = CrudPage::make(__t('title.user_management'))
            ->apiPrefix('/users')
            ->columns($this->getTableColumns())
            ->scrollX(1200)
            ->defaultPageSize(15)
            ->search([
                [__t('search.keyword'), 'keyword', Input::make()->props(['placeholder' => __t('placeholder.keyword_user'), 'clearable' => true])],
                [__t('column.status'), 'status', Select::make()->props([
                    'placeholder' => __t('role.filter_all'),
                    'clearable' => true,
                    'style' => ['width' => '120px'],
                    'options' => [
                        ['label' => __t('tag.enabled'), 'value' => '1'],
                        ['label' => __t('tag.disabled'), 'value' => '0'],
                    ],
                ])],
            ])
            ->toolbarLeft([
                'columnSelector',
                'batchDelete',
                Button::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::batch([
                            'editingId' => null,
                            'formData.username' => '',
                            'formData.nickname' => '',
                            'formData.email' => '',
                            'formData.phone' => '',
                            'formData.password' => '',
                            'formData.roles' => [],
                            'formData.remark' => '',
                            'formData.status' => '1',
                            'formVisible' => true,
                        ]),
                    ])
                    ->text(__t('button.create')),
            ])
            ->toolbarRight([
                'exportCurrent',
                'exportAll',
                'print'
            ])
            ->data([
                'roleOptions' => $this->getRoleOptions(),
                'formData' => $userForm->getDefaultData(),
                'editingId' => null,
                'submitting' => false,
                'resetPwdUserId' => null,
                'resetPwdUserName' => '',
                'newPassword' => '',
                'resetPwdSubmitting' => false,
            ])
            ->methods([
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    IfAction::make('editingId')
                        ->then(
                            FetchAction::make('{{ "/users/" + editingId }}')
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
                            FetchAction::make('/users')
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
            ])
            ->modal('form', '{{ editingId ? "' . __t('title.edit_user') . '" : "' . __t('title.create_user') . '" }}', $userForm, ['width' => '500px'])
            ->modal('resetPwd', __t('user.reset_password_title', ['name' => '{{ resetPwdUserName }}']), $resetPwdForm, ['width' => '400px']);

        return success($schema->build());
    }

    /**
     * 获取表格列配置
     */
    protected function getTableColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID', 'width' => 80],
            ['key' => 'username', 'title' => __t('column.username')],
            ['key' => 'nickname', 'title' => __t('column.nickname')],
            ['key' => 'email', 'title' => __t('column.email')],
            ['key' => 'phone', 'title' => __t('column.phone')],
            ['key' => 'roles', 'title' => __t('column.roles'), 'width' => 150, 'slot' => [
                Space::make()
                    ->props(['size' => 'small'])
                    ->children([
                        Tag::make()
                            ->for('role in slotData.row.roles', '{{ role.id }}')
                            ->props(['type' => 'info', 'size' => 'small'])
                            ->children(['{{ role.title || role.name }}']),
                    ]),
            ]],
            ['key' => 'status', 'title' => __t('column.status'), 'width' => 80, 'slot' => [
                SwitchC::make()
                    ->props(['value' => '{{ slotData.row.status === "1" }}'])
                    ->on('update:value',
                        FetchAction::make('/users/{{ slotData.row.id }}')
                            ->put()
                            ->body(['action_type' => 'status', 'status' => '{{ $event ? "1" : "0" }}'])
                            ->then([
                                CallAction::make('$message.success', [__t('crud.status_updated')]),
                                CallAction::make('loadData'),
                            ])
                            ->catch([
                            CallAction::make('$message.error', ['{{ $error.message || "' . __t('crud.operation_failed') . '" }}']),
                            ])
                    ),
            ]],
            ['key' => 'last_login_time', 'title' => __t('column.last_login_time'), 'width' => 180],
            ['key' => 'created_at', 'title' => __t('column.created_at'), 'width' => 180],
            ['key' => 'actions', 'title' => __t('column.actions'), 'width' => 220, 'fixed' => 'right', 'slot' => [
                Space::make()->children([
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'primary', 'text' => true])
                        ->on('click', [
                            SetAction::make('editingId', '{{ slotData.row.id }}'),
                            SetAction::make('formData.username', '{{ slotData.row.username }}'),
                            SetAction::make('formData.nickname', '{{ slotData.row.nickname || "" }}'),
                            SetAction::make('formData.email', '{{ slotData.row.email || "" }}'),
                            SetAction::make('formData.phone', '{{ slotData.row.phone || "" }}'),
                            SetAction::make('formData.roles', '{{ (slotData.row.roles || []).map(r => r.name) }}'),
                            SetAction::make('formData.remark', '{{ slotData.row.remark || "" }}'),
                            SetAction::make('formData.status', '{{ slotData.row.status }}'),
                            SetAction::make('formVisible', true),
                        ])
                        ->text(__t('button.edit')),
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'warning', 'text' => true])
                        ->on('click', [
                            SetAction::make('resetPwdUserId', '{{ slotData.row.id }}'),
                            SetAction::make('resetPwdUserName', '{{ slotData.row.username }}'),
                            SetAction::make('newPassword', ''),
                            SetAction::make('resetPwdVisible', true),
                        ])
                        ->text(__t('button.reset_password')),
                    Popconfirm::make()
                        ->props([
                            'positiveText' => __t('button.confirm'),
                            'negativeText' => __t('button.cancel'),
                        ])
                        ->on('positive-click',
                            FetchAction::make('/users/{{ slotData.row.id }}')
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
                    ->children([__t('confirm.delete_user_template')]),
                ]),
            ]],
        ];
    }

    /**
     * 获取角色选项（只返回 admin guard 的角色）
     */
    protected function getRoleOptions(): array
    {
        $roleModel = config('lartrix.models.role', \Lartrix\Models\Role::class);
        return $roleModel::query()
            ->where('status', true)
            ->where('guard_name', 'admin')
            ->get(['name', 'title'])
            ->map(fn ($role) => [
                'label' => $role->title ?: $role->name,
                'value' => $role->name,
            ])
            ->toArray();
    }
}
