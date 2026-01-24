<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Services\AuthService;
use Lartrix\Schema\Components\NaiveUI\NInput;
use Lartrix\Schema\Components\NaiveUI\NSelect;
use Lartrix\Schema\Components\NaiveUI\NSwitch;
use Lartrix\Schema\Components\NaiveUI\NButton;
use Lartrix\Schema\Components\NaiveUI\NSpace;
use Lartrix\Schema\Components\NaiveUI\NTag;
use Lartrix\Schema\Components\NaiveUI\NPopconfirm;
use Lartrix\Schema\Components\Business\CrudPage;
use Lartrix\Schema\Components\Business\OptForm;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;
use function Lartrix\Support\success;
use function Lartrix\Support\error;

class UserController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * 获取用户模型类
     */
    protected function getUserModel(): string
    {
        return config('lartrix.models.user', \Lartrix\Models\AdminUser::class);
    }

    /**
     * 获取角色模型类
     */
    protected function getRoleModel(): string
    {
        return config('lartrix.models.role', \Lartrix\Models\Role::class);
    }

    /**
     * 用户列表（分页、搜索）
     */
    public function index(Request $request): array
    {
        $userModel = $this->getUserModel();
        $query = $userModel::query();

        // 搜索
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('nick_name', 'like', "%{$keyword}%")
                    ->orWhere('real_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        }

        // 分页
        $perPage = $request->input('page_size', 15);
        $paginator = $query->with('roles')->orderBy('id', 'desc')->paginate($perPage);

        return success([
            'list' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'page_size' => $paginator->perPage(),
        ]);
    }

    /**
     * 创建用户
     */
    public function store(Request $request): array
    {
        $userModel = $this->getUserModel();
        $table = config('lartrix.tables.users', 'admin_users');

        $validated = $request->validate([
            'name' => "required|string|max:255|unique:{$table}",
            'nick_name' => 'nullable|string|max:255',
            'real_name' => 'nullable|string|max:255',
            'email' => "required|email|unique:{$table}",
            'mobile' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'status' => 'boolean',
            'avatar' => 'nullable|string',
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = $userModel::create([
            'name' => $validated['name'],
            'nick_name' => $validated['nick_name'] ?? null,
            'real_name' => $validated['real_name'] ?? null,
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? null,
            'password' => $validated['password'],
            'status' => $validated['status'] ?? true,
            'avatar' => $validated['avatar'] ?? null,
        ]);

        // 分配角色
        if (!empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return success('创建成功', $user->load('roles'));
    }

    /**
     * 用户详情
     */
    public function show(int $id): array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::with('roles')->find($id);

        if (!$user) {
            error('用户不存在', null, 40004);
        }

        return success($user->toArray());
    }

    /**
     * 更新用户
     */
    public function update(Request $request, int $id): array
    {
        $userModel = $this->getUserModel();
        $table = config('lartrix.tables.users', 'admin_users');
        $user = $userModel::find($id);

        if (!$user) {
            error('用户不存在', null, 40004);
        }

        $validated = $request->validate([
            'name' => "string|max:255|unique:{$table},name,{$id}",
            'nick_name' => 'nullable|string|max:255',
            'real_name' => 'nullable|string|max:255',
            'email' => "email|unique:{$table},email,{$id}",
            'mobile' => 'nullable|string|max:20',
            'avatar' => 'nullable|string',
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->fill($validated);
        $user->save();

        // 更新角色
        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return success('更新成功', $user->load('roles'));
    }

    /**
     * 删除用户
     */
    public function destroy(int $id): array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::find($id);

        if (!$user) {
            error('用户不存在', null, 40004);
        }

        // 撤销所有 Token
        $this->authService->revokeAllTokens($user);

        $user->delete();

        return success('删除成功');
    }

    /**
     * 更新用户状态
     */
    public function updateStatus(Request $request, int $id): array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::find($id);

        if (!$user) {
            error('用户不存在', null, 40004);
        }

        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $user->status = $validated['status'];
        $user->save();

        // 禁用时撤销所有 Token
        if (!$user->status) {
            $this->authService->revokeAllTokens($user);
        }

        return success('状态更新成功', ['status' => $user->status]);
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request, int $id): array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::find($id);

        if (!$user) {
            error('用户不存在', null, 40004);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->password = $validated['password'];
        $user->save();

        // 撤销所有 Token，强制重新登录
        $this->authService->revokeAllTokens($user);

        return success('密码重置成功');
    }

    /**
     * 用户列表页 UI Schema
     */
    public function listUi(): array
    {
        // 用户表单
        $userForm = OptForm::make('formData')
            ->fields([
                ['用户名', 'name', NInput::make()->props(['placeholder' => '请输入用户名', 'disabled' => '{{ !!editingId }}'])],
                ['昵称', 'nick_name', NInput::make()->props(['placeholder' => '请输入昵称'])],
                ['真实姓名', 'real_name', NInput::make()->props(['placeholder' => '请输入真实姓名'])],
                ['邮箱', 'email', NInput::make()->props(['placeholder' => '请输入邮箱'])],
                ['手机号', 'mobile', NInput::make()->props(['placeholder' => '请输入手机号'])],
                ['密码', 'password', NInput::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入密码']), '', '!editingId'],
                ['角色', 'roles', NSelect::make()->props(['multiple' => true, 'placeholder' => '请选择角色', 'options' => '{{ roleOptions }}']), []],
                ['状态', 'status', NSwitch::make(), true],
            ])
            ->buttons([
                NButton::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                NButton::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        // 重置密码表单
        $resetPwdForm = OptForm::make()
            ->fields([
                ['新密码', 'newPassword', NInput::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入新密码（至少6位）'])],
            ])
            ->buttons([
                NButton::make()->on('click', SetAction::make('resetPwdVisible', false))->text('取消'),
                NButton::make()->type('primary')->props(['loading' => '{{ resetPwdSubmitting }}'])->on('click', [
                    SetAction::make('resetPwdSubmitting', true),
                    FetchAction::make('/users/{{ resetPwdUserId }}/reset-password')
                        ->put()
                        ->body(['password' => '{{ newPassword }}'])
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
            // 搜索区域
            ->search([
                ['关键词', 'keyword', NInput::make()->props(['placeholder' => '用户名/昵称/邮箱/手机号', 'clearable' => true])],
                ['状态', 'status', NSelect::make()->props([
                    'placeholder' => '全部',
                    'clearable' => true,
                    'style' => ['width' => '120px'],
                    'options' => [
                        ['label' => '启用', 'value' => true],
                        ['label' => '禁用', 'value' => false],
                    ],
                ])],
            ])
            // 工具栏
            ->toolbarLeft([
                'columnSelector',
                'batchDelete',
                NButton::make()
                    ->type('primary')
                    ->on('click', [
                        SetAction::batch([
                            'editingId' => null,
                            'formData.name' => '',
                            'formData.nick_name' => '',
                            'formData.real_name' => '',
                            'formData.email' => '',
                            'formData.mobile' => '',
                            'formData.password' => '',
                            'formData.roles' => [],
                            'formData.status' => true,
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
            // 数据
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
            // 方法（只保留重复使用的）
            ->methods([
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    FetchAction::make('{{ editingId ? "/users/" + editingId : "/users" }}')
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
            // 表单弹窗
            ->modal('form', '{{ editingId ? "编辑用户" : "新增用户" }}', $userForm, ['width' => '500px'])
            // 重置密码弹窗
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
            ['key' => 'name', 'title' => '用户名'],
            ['key' => 'nick_name', 'title' => '昵称'],
            ['key' => 'email', 'title' => '邮箱'],
            ['key' => 'mobile', 'title' => '手机号'],
            ['key' => 'roles', 'title' => '角色', 'width' => 150, 'slot' => [
                NSpace::make()
                    ->props(['size' => 'small'])
                    ->children([
                        NTag::make()
                            ->for('role in slotData.row.roles', '{{ role.id }}')
                            ->props(['type' => 'info', 'size' => 'small'])
                            ->children(['{{ role.title || role.name }}']),
                    ]),
            ]],
            ['key' => 'status', 'title' => '状态', 'width' => 80, 'slot' => [
                NSwitch::make()
                    ->props(['value' => '{{ slotData.row.status }}'])
                    ->on('update:value', 
                        FetchAction::make('/users/{{ slotData.row.id }}/status')
                            ->put()
                            ->body(['status' => '{{ $event }}'])
                            ->then([
                                CallAction::make('$message.success', ['状态更新成功']),
                                CallAction::make('loadData'),
                            ])
                            ->catch([
                                CallAction::make('$message.error', ['{{ $error.message || "状态更新失败" }}']),
                            ])
                    ),
            ]],
            ['key' => 'created_at', 'title' => '创建时间', 'width' => 180],
            ['key' => 'actions', 'title' => '操作', 'width' => 220, 'fixed' => 'right', 'slot' => [
                NSpace::make()->children([
                    NButton::make()
                        ->size('small')
                        ->props(['type' => 'primary', 'text' => true])
                        ->on('click', [
                            SetAction::make('editingId', '{{ slotData.row.id }}'),
                            SetAction::make('formData.name', '{{ slotData.row.name }}'),
                            SetAction::make('formData.nick_name', '{{ slotData.row.nick_name || "" }}'),
                            SetAction::make('formData.real_name', '{{ slotData.row.real_name || "" }}'),
                            SetAction::make('formData.email', '{{ slotData.row.email }}'),
                            SetAction::make('formData.mobile', '{{ slotData.row.mobile || "" }}'),
                            SetAction::make('formData.roles', '{{ (slotData.row.roles || []).map(r => r.name) }}'),
                            SetAction::make('formData.status', '{{ slotData.row.status }}'),
                            SetAction::make('formVisible', true),
                        ])
                        ->text('编辑'),
                    NButton::make()
                        ->size('small')
                        ->props(['type' => 'warning', 'text' => true])
                        ->on('click', [
                            SetAction::make('resetPwdUserId', '{{ slotData.row.id }}'),
                            SetAction::make('resetPwdUserName', '{{ slotData.row.name }}'),
                            SetAction::make('newPassword', ''),
                            SetAction::make('resetPwdVisible', true),
                        ])
                        ->text('重置密码'),
                    NPopconfirm::make()
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
                            NButton::make()
                                ->size('small')
                                ->props(['type' => 'error', 'text' => true])
                                ->text('删除'),
                        ])
                        ->children(['确定要删除用户 {{ slotData.row.name }} 吗？']),
                ]),
            ]],
        ];
    }

    /**
     * 获取角色选项
     */
    protected function getRoleOptions(): array
    {
        $roleModel = $this->getRoleModel();
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
