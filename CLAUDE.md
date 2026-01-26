# CLAUDE.md - Lartrix 开发指南

本文档为 Claude AI 提供项目上下文和开发指南。

## 项目概述

Lartrix 是一个 Laravel 后台管理包，为 Trix 前端提供 API 接口。支持用户管理、角色权限、菜单管理、系统设置等功能，并提供 PHP Schema Builder 用于生成 vschema-ui 兼容的 JSON Schema。

## 技术栈

- PHP 8.1+, Laravel 10/11/12
- Laravel Sanctum（认证）
- Spatie Laravel Permission（权限管理）
- nwidart/laravel-modules（模块化开发）
- Maatwebsite Excel（导出）

## 开发模式

### 推荐：模块化开发

```bash
php artisan module:make Blog
```

模块结构：
```
Modules/Blog/
├── Http/Controllers/
├── Models/
├── Database/Migrations/
├── Routes/api.php
└── composer.json
```

### 控制器开发

继承 `CrudController` 实现 CRUD：

```php
<?php

namespace Modules\Blog\Http\Controllers;

use Lartrix\Controllers\CrudController;
use Lartrix\Schema\Components\NaiveUI\{Input, Select, SwitchC, Button, Space, Popconfirm};
use Lartrix\Schema\Components\Business\{CrudPage, OptForm};
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};

class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return \Modules\Blog\Models\Post::class;
    }

    protected function getResourceName(): string
    {
        return '文章';
    }

    protected function listUi(): array
    {
        $schema = CrudPage::make('文章管理')
            ->apiPrefix('/blog/posts')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'title', 'title' => '标题'],
                ['key' => 'status', 'title' => '状态', 'slot' => [
                    SwitchC::make()
                        ->props(['value' => '{{ slotData.row.status }}'])
                        ->on('update:value', 
                            FetchAction::make('/blog/posts/{{ slotData.row.id }}')
                                ->put()
                                ->body(['action_type' => 'status', 'status' => '{{ $event }}'])
                                ->then([CallAction::make('$message.success', ['更新成功']), CallAction::make('loadData')])
                        ),
                ]],
            ])
            ->search([['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索', 'clearable' => true])]])
            ->toolbarLeft([Button::make()->type('primary')->on('click', [SetAction::make('formVisible', true)])->text('新增')]);

        return success($schema->build());
    }
}
```

## Schema 组件

### 命名规则

NaiveUI 组件类名无 N 前缀，输出保留 N 前缀：
- `Button::make()` → `{ "com": "NButton" }`
- `SwitchC::make()` → `{ "com": "NSwitch" }`（Switch 是保留字）
- `EmptyState::make()` → `{ "com": "NEmpty" }`（Empty 是保留字）
- `ListC::make()` → `{ "com": "NList" }`（List 是保留字）

### 常用组件

```php
// NaiveUI 组件（120+）
use Lartrix\Schema\Components\NaiveUI\{
    Button, Input, Select, SwitchC, Tag,
    Card, Modal, Drawer, Form, FormItem,
    Space, Flex, Grid, Row, Col,
    Popconfirm, Popover, Tooltip, Dropdown,
    DataTable, Pagination, Tabs, TabPane,
    DatePicker, TimePicker, ColorPicker,
    Upload, Tree, TreeSelect, Cascader,
    Alert, Badge, Avatar, Progress
};

// 业务组件
use Lartrix\Schema\Components\Business\{
    CrudPage, OptForm, FlowEditor,
    MarkdownEditor, RichEditor, IconPicker
};

// 自定义组件
use Lartrix\Schema\Components\Custom\{
    Html, SvgIcon, Icon, VueECharts,
    ButtonIcon, CountTo, FullScreen
};

// JSON 组件
use Lartrix\Schema\Components\Json\{JsonDataTable, SchemaEditor};

// Action 类型
use Lartrix\Schema\Actions\{
    SetAction, CallAction, FetchAction,
    IfAction, ScriptAction, EmitAction,
    CopyAction, WebSocketAction
};
```

### Component 基类方法

```php
->props(array $props)           // 属性
->children(array|string $c)     // 子节点
->on(string $event, $handler)   // 事件
->slot(string $name, array $c)  // 插槽
->if(string $expr)              // v-if
->show(string $expr)            // v-show
->for(string $expr, ?string $key) // v-for
->model(string|array $model)    // v-model
->data(array $data)             // 响应式数据
->methods(array $methods)       // 方法
->onMounted($actions)           // 挂载回调
```

### CrudPage 方法

```php
CrudPage::make('标题')
    ->apiPrefix('/api/path')
    ->apiParams(['key' => 'value'])
    ->columns([...])
    ->scrollX(1200)
    ->pagination(true)
    ->defaultPageSize(15)
    ->tree('children', false)
    ->search([...])
    ->toolbarLeft([...])
    ->toolbarRight([...])
    ->data([...])
    ->methods([...])
    ->modal('name', '标题', $form)
    ->drawer('name', '标题', $form)
    ->build()
```

### OptForm 表单

```php
OptForm::make('formData')
    ->fields([
        ['标题', 'title', Input::make()],
        ['状态', 'status', SwitchC::make(), true],  // 默认值
    ])
    ->buttons([
        Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
        Button::make()->type('primary')->on('click', ['call' => 'handleSubmit'])->text('确定'),
    ])
```

## API 响应

```php
// 全局函数，无需 use
return success('操作成功', $data);
return success($data);
error('错误信息', null, 40004);
```

## CrudController action_type

### index 方法（GET）

| action_type | 说明 |
|-------------|------|
| list（默认） | 列表数据 |
| list_ui | 列表 Schema |
| form_ui | 表单 Schema |
| export | 导出数据 |
| batch_destroy | 批量删除 |

### update 方法（PUT）

| action_type | 说明 |
|-------------|------|
| update（默认） | 更新记录 |
| status | 更新状态 |
| 自定义 | 子类定义 `updateXxx` 方法 |

### destroy 方法（DELETE）

| action_type | 说明 |
|-------------|------|
| delete（默认） | 删除记录 |
| batch | 批量删除 |

### CrudController 可重写方法

```php
// 配置
protected function getModelClass(): string;      // 必须实现
protected function getResourceName(): string;
protected function getDefaultOrder(): array;
protected function getDefaultPageSize(): int;
protected function getListWith(): array;
protected function getExportColumns(): array;

// 查询
protected function applySearch(Builder $query, Request $request): void;
protected function applyFilters(Builder $query, Request $request): void;

// 验证
protected function getStoreRules(): array;
protected function getUpdateRules(int $id): array;

// 数据处理
protected function prepareStoreData(array $validated): array;
protected function prepareUpdateData(array $validated): array;

// 回调
protected function afterStore(mixed $model, array $validated): void;
protected function afterUpdate(mixed $model, array $validated): void;
protected function afterStatusUpdate(mixed $model, bool $status): void;
protected function beforeDelete(mixed $model): void;
protected function afterDelete(mixed $model): void;

// UI Schema
protected function listUi(): array;
protected function formUi(): array;
```

## 开发规范

1. 控制器继承 `CrudController`
2. 使用 `success()` / `error()` 响应
3. Schema 组件链式调用
4. `$request->filled()` 检查非空参数
5. action_type 下划线格式：`list_ui`, `form_ui`
