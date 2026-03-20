# CrudController

CrudController 是 Lartrix 提供的 CRUD 基类，让你快速实现标准的增删改查功能。

## 基础用法

继承 CrudController 并实现必要方法：

```php
<?php

namespace Modules\\Blog\\Http\\Controllers;

use Lartrix\\Controllers\\CrudController;
use Modules\\Blog\\Models\\Post;

class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return Post::class;
    }

    protected function getResourceName(): string
    {
        return '文章';
    }
}
```

这样就自动获得了以下接口：

| 方法 | 路由 | 说明 |
|------|------|------|
| GET | /posts | 列表 + 列表 UI |
| POST | /posts | 创建 |
| PUT | /posts/{id} | 更新 |
| DELETE | /posts/{id} | 删除 |

## 可配置方法

### 基础配置

```php
// 模型类（必需）
protected function getModelClass(): string;

// 资源名称（用于提示消息）
protected function getResourceName(): string;

// 默认排序
protected function getDefaultOrder(): array
{
    return ['created_at' => 'desc'];
}

// 默认分页大小
protected function getDefaultPageSize(): int
{
    return 15;
}

// 关联加载
protected function getListWith(): array
{
    return ['author', 'category'];
}
```

### 查询定制

```php
// 搜索逻辑
protected function applySearch($query, $request): void
{
    if ($request->filled('keyword')) {
        $query->where('title', 'like', "%{$request->keyword}%");
    }
}

// 过滤器逻辑
protected function applyFilters($query, $request): void
{
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
}
```

### 验证规则

```php
// 创建验证规则
protected function getStoreRules(): array
{
    return [
        'title' => 'required|string|max:255',
        'content' => 'nullable|string',
        'status' => 'boolean',
    ];
}

// 更新验证规则（带 ID 参数）
protected function getUpdateRules(int $id): array
{
    return [
        'title' => 'required|string|max:255',
        'content' => 'nullable|string',
        'status' => 'boolean',
    ];
}
```

### 数据处理

```php
// 创建前数据处理
protected function prepareStoreData(array $validated): array
{
    $validated['slug'] = Str::slug($validated['title']);
    return $validated;
}

// 更新前数据处理
protected function prepareUpdateData(array $validated): array
{
    return $validated;
}
```

### 生命周期钩子

```php
// 创建后
protected function afterStore($model, $validated): void
{
    // 发送通知、更新缓存等
}

// 更新后
protected function afterUpdate($model, $validated): void
{
    // 记录日志等
}

// 状态更新后
protected function afterStatusUpdate($model, $status): void
{
    // 状态变更处理
}

// 删除前
protected function beforeDelete($model): void
{
    // 检查关联数据等
}

// 删除后
protected function afterDelete($model): void
{
    // 清理关联数据等
}
```

## action_type 详解

### index 方法支持的 action_type

```php
// 默认，返回列表数据
GET /posts

// 返回列表 UI Schema
GET /posts?action_type=list_ui

// 返回表单 UI Schema
GET /posts?action_type=form_ui

// 导出数据
GET /posts?action_type=export

// 批量删除
POST /posts?action_type=batch_destroy
    Body: {ids: [1, 2, 3]}
```

### update 方法支持的 action_type

```php
// 默认，更新记录
PUT /posts/{id}

// 更新状态（会调用 afterStatusUpdate）
PUT /posts/{id}?action_type=status
    Body: {status: false}

// 自定义 action
PUT /posts/{id}?action_type=publish
    // 会自动调用 updatePublish 方法
```

### destroy 方法支持的 action_type

```php
// 默认，删除记录
DELETE /posts/{id}

// 批量删除
DELETE /posts?action_type=batch
    Body: {ids: [1, 2, 3]}
```

## UI 定义

### 列表 UI

```php
protected function listUi(): array
{
    $schema = CrudPage::make('文章管理')
        ->apiPrefix('/blog/posts')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'title', 'title' => '标题'],
            ['key' => 'status', 'title' => '状态'],
        ]);

    return success(\$schema->build());
}
```

### 表单 UI

```php
protected function formUi(): array
{
    \$form = OptForm::make('formData')
        $table->fields([
            ['标题', 'title', Input::make()],
            ['内容', 'content', Input::make()->type('textarea')],
            ['状态', 'status', SwitchC::make(), true],
        ]);

    return success(['form' => \$form->build()]);
}
```

## 完整示例

```php
class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return Post::class;
    }

    protected function getResourceName(): string
    {
        return '文章';
    }

    protected function getDefaultOrder(): array
    {
        return ['created_at' => 'desc'];
    }

    protected function getListWith(): array
    {
        return ['author'];
    }

    protected function applySearch($query, $request): void
    {
        if ($request->filled('keyword')) {
            $query->where('title', 'like', "%{$request->keyword}%");
        }
    }

    protected function getStoreRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ];
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('文章管理')
            ->apiPrefix('/blog/posts')
            ->columns([
                ['key' => 'id', 'title' => 'ID'],
                ['key' => 'title', 'title' => '标题'],
                ['key' => 'author.name', 'title' => '作者'],
                ['key' => 'created_at', 'title' => '创建时间'],
            ])
            ->search([
                ['关键词', 'keyword', Input::make()]
            ]);

        return success($schema->build());
    }
}
```
