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
        return '用户';
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
        return '用户列表';
    }

    protected function getExportColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'username', 'title' => '用户名'],
            ['key' => 'nickname', 'title' => '昵称'],
            ['key' => 'email', 'title' => '邮箱'],
            ['key' => 'phone', 'title' => '手机号'],
            ['key' => 'roles', 'title' => '角色'],
            ['key' => 'status', 'title' => '状态'],
            ['key' => 'last_login_time', 'title' => '最后登录时间'],
            ['key' => 'created_at', 'title' => '创建时间'],
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

        return success('状态更新成功', ['status' => $model->status]);
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

        return success('密码重置成功');
    }

    // ==================== UI Schema ====================

    protected function listUi(): array
    {
        // 用户表单
        $userForm = OptForm::make('formData')
            ->fields([
                ['用户名', 'username', Input::make()->props(['placeholder' => '请输入用户名', 'disabled' => '{{ !!editingId }}'])],
                ['昵称', 'nickname', Input::make()->props(['placeholder' => '请输入昵称'])],
                ['邮箱', 'email', Input::make()->props(['placeholder' => '请输入邮箱'])],
                ['手机号', 'phone', Input::make()->props(['placeholder' => '请输入手机号'])],
                ['密码', 'password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入密码']), '', '!editingId'],
                ['角色', 'roles', Select::make()->props(['multiple' => true, 'placeholder' => '请选择角色', 'options' => '{{ roleOptions }}']), []],
                ['备注', 'remark', Input::make()->props(['type' => 'textarea', 'placeholder' => '请输入备注'])],
                ['状态', 'status', SwitchC::make()->props(['checkedValue' => '1', 'uncheckedValue' => '0']), '1'],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        // 重置密码表单
        $resetPwdForm = OptForm::make()
            ->fields([
                ['新密码', 'newPassword', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入新密码（至少6位）'])],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('resetPwdVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ resetPwdSubmitting }}'])->on('click', [
                    SetAction::make('resetPwdSubmitting', true),
                    FetchAction::make('/users/{{ resetPwdUserId }}')
                        ->put()
                        ->body(['action_type' => 'reset_password', 'password' => '{{ newPassword }}'])
                        ->then([
                            CallAction::make('$message.success', ['密码重置成功']),
                            SetAction::make('resetPwdVisible', false),
                        ])
                        ->catch([
                            CallAction::make('$message.error', ['{{ $error.message || "密码重置失败" }}']),
                        ])
                        ->finally([
                            SetAction::make('resetPwdSubmitting', false),
                        ]),
                ])->text('确定'),
            ]);

        $schema = CrudPage::make('用户管理')
            ->apiPrefix('/users')
            ->columns($this->getTableColumns())
            ->scrollX(1200)
            ->defaultPageSize(15)
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '用户名/昵称/邮箱/手机号', 'clearable' => true])],
                ['状态', 'status', Select::make()->props([
                    'placeholder' => '全部',
                    'clearable' => true,
                    'style' => ['width' => '120px'],
                    'options' => [
                        ['label' => '启用', 'value' => '1'],
                        ['label' => '禁用', 'value' => '0'],
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
                    ->text('新增'),
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
                                    CallAction::make('$message.success', ['更新成功']),
                                    SetAction::make('formVisible', false),
                                    CallAction::make('loadData'),
                                ])
                                ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}']),
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
                                    CallAction::make('$message.success', ['创建成功']),
                                    SetAction::make('formVisible', false),
                                    CallAction::make('loadData'),
                                ])
                                ->catch([
                                    CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}']),
                                ])
                                ->finally([
                                    SetAction::make('submitting', false),
                                ])
                        ),
                ],
            ])
            ->modal('form', '{{ editingId ? "编辑用户" : "新增用户" }}', $userForm, ['width' => '500px'])
            ->modal('resetPwd', '重置密码 - {{ resetPwdUserName }}', $resetPwdForm, ['width' => '400px']);

        return success($schema->build());
    }

    /**
     * 获取表格列配置
     */
    protected function getTableColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID', 'width' => 80],
            ['key' => 'username', 'title' => '用户名'],
            ['key' => 'nickname', 'title' => '昵称'],
            ['key' => 'email', 'title' => '邮箱'],
            ['key' => 'phone', 'title' => '手机号'],
            ['key' => 'roles', 'title' => '角色', 'width' => 150, 'slot' => [
                Space::make()
                    ->props(['size' => 'small'])
                    ->children([
                        Tag::make()
                            ->for('role in slotData.row.roles', '{{ role.id }}')
                            ->props(['type' => 'info', 'size' => 'small'])
                            ->children(['{{ role.title || role.name }}']),
                    ]),
            ]],
            ['key' => 'status', 'title' => '状态', 'width' => 80, 'slot' => [
                SwitchC::make()
                    ->props(['value' => '{{ slotData.row.status === "1" }}'])
                    ->on('update:value',
                        FetchAction::make('/users/{{ slotData.row.id }}')
                            ->put()
                            ->body(['action_type' => 'status', 'status' => '{{ $event ? "1" : "0" }}'])
                            ->then([
                                CallAction::make('$message.success', ['状态更新成功']),
                                CallAction::make('loadData'),
                            ])
                            ->catch([
                                CallAction::make('$message.error', ['{{ $error.message || "状态更新失败" }}']),
                            ])
                    ),
            ]],
            ['key' => 'last_login_time', 'title' => '最后登录', 'width' => 180],
            ['key' => 'created_at', 'title' => '创建时间', 'width' => 180],
            ['key' => 'actions', 'title' => '操作', 'width' => 220, 'fixed' => 'right', 'slot' => [
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
                        ->text('编辑'),
                    Button::make()
                        ->size('small')
                        ->props(['type' => 'warning', 'text' => true])
                        ->on('click', [
                            SetAction::make('resetPwdUserId', '{{ slotData.row.id }}'),
                            SetAction::make('resetPwdUserName', '{{ slotData.row.username }}'),
                            SetAction::make('newPassword', ''),
                            SetAction::make('resetPwdVisible', true),
                        ])
                        ->text('重置密码'),
                    Popconfirm::make()
                        ->props([
                            'positiveText' => '确定',
                            'negativeText' => '取消',
                        ])
                        ->on('positive-click',
                            FetchAction::make('/users/{{ slotData.row.id }}')
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
                        ->children(['确定要删除用户 {{ slotData.row.username }} 吗？']),
                ]),
            ]],
        ];
    }

    /**
     * 获取角色选项
     */
    protected function getRoleOptions(): array
    {
        $roleModel = config('lartrix.models.role', \Lartrix\Models\Role::class);
        return $roleModel::query()
            ->where('status', true)
            ->get(['name', 'title'])
            ->map(fn ($role) => [
                'label' => $role->title ?: $role->name,
                'value' => $role->name,
            ])
            ->toArray();
    }
}
