# 用户管理示例

完整的用户管理 CRUD 实现，包含搜索、筛选、状态切换等功能。

## 创建迁移

```bash
php artisan module:make-migration create_users_table User
```

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('phone')->nullable();
    $table->string('avatar')->nullable();
    $table->boolean('status')->default(true);
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
```

## 模型

```php
<?php

namespace Modules\\User\\Models;

use Illuminate\\Foundation\\Auth\\User as Authenticatable;
use Illuminate\\Notifications\\Notifiable;
use Laravel\\Sanctum\\HasApiTokens;
use Spatie\\Permission\\Traits\\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'status',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'boolean',
    ];
}
```

## 控制器

```php
<?php

namespace Modules\\User\\Http\\Controllers;

use Illuminate\\Http\\Request;
use Lartrix\\Controllers\\CrudController;
use Lartrix\\Schema\\Components\\NaiveUI\\{
    Button, Input, Select, SwitchC, Space, Popconfirm
};
use Lartrix\\Schema\\Components\\Business\\CrudPage;
use Lartrix\\Schema\\Actions\\{SetAction, CallAction, FetchAction};
use Modules\\User\\Models\\User;

class UserController extends CrudController
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function getResourceName(): string
    {
        return '用户';
    }

    protected function getDefaultOrder(): array
    {
        return ['created_at' => 'desc'];
    }

    protected function applySearch($query, Request $request): void
    {
        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                  $table->orWhere('email', 'like', "%{$request->keyword}%");
            });
        }
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    }

    protected function getStoreRules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'status' => 'boolean',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        return [
            'name' => 'required|string|max:50',
            'email' => "required|email|unique:users,email,{$id}",
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'status' => 'boolean',
        ];
    }

    protected function prepareStoreData(array $validated): array
    {
        $validated['password'] = bcrypt($validated['password']);
        return $validated;
    }

    protected function prepareUpdateData(array $validated): array
    {
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = bcrypt($validated['password']);
        }
        return $validated;
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('用户管理')
            ->apiPrefix('/user/users')
            ->columns([
                [
                    'key' => 'id',
                    'title' => 'ID',
                    'width' => 80,
                ],
                [
                    'key' => 'name',
                    'title' => '姓名',
                ],
                [
                    'key' => 'email',
                    'title' => '邮箱',
                ],
                [
                    'key' => 'phone',
                    'title' => '电话',
                ],
                [
                    'key' => 'status',
                    'title' => '状态',
                    'slot' => [
                        SwitchC::make()
                            ->props([
                                'value' => '{{ slotData.row.status }}',
                                'checkedValue' => true,
                                'uncheckedValue' => false,
                            ])
                            ->on('update:value',
                                FetchAction::make('/user/users/{{ slotData.row.id }}')
                                    ->put()
                                    ->body(['action_type' => 'status', 'status' => '{{ $event }}'])
                                    ->then([
                                        CallAction::make('$message.success', ['状态更新成功']),
                                    ])
                            ),
                    ],
                ],
                [
                    'key' => 'created_at',
                    'title' => '创建时间',
                ],
            ])
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '姓名/邮箱'])->clearable()],
                ['状态', 'status', Select::make()->options([
                    ['label' => '全部', 'value' => ''],
                    ['label' => '启用', 'value' => 1],
                    ['label' => '禁用', 'value' => 0],
                ])],
            ]);

        return success($schema->build());
    }
}
```

## 路由

```php
Route::middleware(['auth:admin', 'permission:users.*'])->group(function () {
    Route::resource('users', UserController::class);
});
```

## 效果预览

- 列表展示：ID、姓名、邮箱、电话、状态、创建时间
- 搜索功能：按姓名/邮箱搜索
- 状态筛选：按启用/禁用筛选
- 状态切换：点击开关快速切换用户状态
- 分页功能：自动分页，支持调整每页数量
