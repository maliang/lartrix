# AGENTS.md - Lartrix 开发指南

本文档为 AI 代理提供项目上下文和开发指南。

## 项目概述

Lartrix 是一个 Laravel 后台管理包，为 Trix 前端提供 API 接口。支持用户管理、角色权限、菜单管理、系统设置等功能，并提供 PHP Schema Builder 用于生成 vschema-ui 兼容的 JSON Schema。

## 技术栈

- PHP 8.1+
- Laravel 10/11/12
- Laravel Sanctum（认证）
- Spatie Laravel Permission（权限管理）
- nwidart/laravel-modules（模块化开发）
- Maatwebsite Excel（导出）

## 目录结构

```
lartrix/
├── config/lartrix.php          # 包配置文件
├── routes/api.php              # API 路由
├── resources/admin/            # 前端静态资源（由 trix 构建）
├── stubs/migrations/           # 数据库迁移模板
├── src/
│   ├── Commands/               # Artisan 命令
│   │   ├── InstallCommand.php
│   │   ├── PublishAssetsCommand.php
│   │   └── UninstallCommand.php
│   ├── Controllers/            # API 控制器
│   │   ├── Controller.php      # 基础控制器
│   │   ├── CrudController.php  # CRUD 基类（推荐继承）
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── RoleController.php
│   │   ├── PermissionController.php
│   │   ├── MenuController.php
│   │   ├── ModuleController.php
│   │   ├── SettingController.php
│   │   ├── SystemController.php
│   │   └── HomeController.php
│   ├── Middleware/             # 中间件
│   │   ├── Authenticate.php
│   │   └── CheckPermission.php
│   ├── Models/                 # Eloquent 模型
│   │   ├── AdminUser.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── Menu.php
│   │   ├── Module.php
│   │   └── Setting.php
│   ├── Services/               # 业务服务
│   │   ├── AuthService.php
│   │   ├── ModuleService.php
│   │   └── PermissionService.php
│   ├── Schema/                 # JSON Schema 构建器
│   │   ├── JsonNodeInterface.php
│   │   ├── Actions/            # Action 类型
│   │   └── Components/         # 组件类型
│   ├── Exceptions/
│   │   └── ApiException.php
│   ├── Exports/
│   │   └── BaseExport.php
│   └── LartrixServiceProvider.php
└── tests/
```

## 开发模式

### 推荐：使用模块化开发

Lartrix 推荐使用 `nwidart/laravel-modules` 进行模块化开发：

```bash
# 创建新模块
php artisan module:make Blog

# 模块结构
Modules/Blog/
├── Config/
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Http/
│   └── Controllers/
├── Models/
├── Providers/
├── Resources/
├── Routes/
│   ├── api.php
│   └── web.php
└── composer.json
```

### 控制器开发

继承 `CrudController` 快速实现 CRUD 功能：

```php
<?php

namespace Modules\Blog\Http\Controllers;

use Lartrix\Controllers\CrudController;
use Lartrix\Schema\Components\NaiveUI\{Input, Select, SwitchC, Button, Space, Tag, Popconfirm};
use Lartrix\Schema\Components\Business\{CrudPage, OptForm};
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};

class PostController extends CrudController
{
    // 配置模型
    protected function getModelClass(): string
    {
        return \Modules\Blog\Models\Post::class;
    }

    protected function getResourceName(): string
    {
        return '文章';
    }

    // 列表 UI（action_type=list_ui）
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
                                ->then([CallAction::make('$message.success', ['状态更新成功']), CallAction::make('loadData')])
                        ),
                ]],
                ['key' => 'actions', 'title' => '操作', 'slot' => [
                    Space::make()->children([
                        Button::make()->size('small')->props(['type' => 'primary', 'text' => true])
                            ->on('click', [SetAction::make('editingId', '{{ slotData.row.id }}'), SetAction::make('formVisible', true)])
                            ->text('编辑'),
                        Popconfirm::make()
                            ->on('positive-click', FetchAction::make('/blog/posts/{{ slotData.row.id }}')->delete()->then([CallAction::make('loadData')]))
                            ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text('删除')])
                            ->children(['确定删除？']),
                    ]),
                ]],
            ])
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索标题', 'clearable' => true])],
            ])
            ->toolbarLeft(['columnSelector', 'batchDelete', Button::make()->type('primary')->on('click', [SetAction::make('formVisible', true)])->text('新增')]);

        return success($schema->build());
    }
}
```

## Schema 构建器

### 组件命名规则

NaiveUI 组件类名无 N 前缀，但输出的 `com` 字段保留 N 前缀：
- `Button::make()` → `{ "com": "NButton" }`
- `Input::make()` → `{ "com": "NInput" }`
- `SwitchC::make()` → `{ "com": "NSwitch" }`（Switch 是 PHP 保留字）
- `EmptyState::make()` → `{ "com": "NEmpty" }`（Empty 是 PHP 保留字）
- `ListC::make()` → `{ "com": "NList" }`（List 是 PHP 保留字）

### 组件目录

```
Schema/Components/
├── Component.php               # 基类
├── NaiveUI/                    # NaiveUI 组件（120+）
│   ├── Button.php, Input.php, Select.php, SwitchC.php
│   ├── Card.php, Modal.php, Drawer.php, Form.php, FormItem.php
│   ├── DataTable.php, Pagination.php, Tag.php
│   ├── Tabs.php, TabPane.php, Collapse.php, CollapseItem.php
│   ├── Grid.php, GridItem.php, Row.php, Col.php, Flex.php, Space.php
│   ├── DatePicker.php, TimePicker.php, ColorPicker.php
│   ├── Upload.php, Tree.php, TreeSelect.php, Cascader.php
│   ├── Popconfirm.php, Popover.php, Tooltip.php, Dropdown.php
│   ├── Alert.php, Badge.php, Avatar.php, Progress.php
│   ├── Steps.php, Timeline.php, Descriptions.php
│   └── ...
├── Business/                   # 业务组件
│   ├── CrudPage.php            # CRUD 页面（推荐）
│   ├── OptForm.php             # 表单构建器
│   ├── FlowEditor.php          # 流程编辑器
│   ├── MarkdownEditor.php      # Markdown 编辑器
│   ├── RichEditor.php          # 富文本编辑器
│   └── IconPicker.php          # 图标选择器
├── Custom/                     # 自定义组件
│   ├── Html.php                # 原生 HTML 标签
│   ├── SvgIcon.php             # SVG 图标
│   ├── Icon.php                # 图标组件
│   ├── VueECharts.php          # ECharts 图表
│   ├── ButtonIcon.php          # 图标按钮
│   ├── CountTo.php             # 数字动画
│   ├── FullScreen.php          # 全屏切换
│   ├── GlobalSearch.php        # 全局搜索
│   ├── HeaderNotification.php  # 头部通知
│   ├── LangSwitch.php          # 语言切换
│   ├── ThemeButton.php         # 主题按钮
│   ├── ThemeSchemaSwitch.php   # 主题模式切换
│   ├── UserAvatar.php          # 用户头像
│   └── BetterScroll.php        # 滚动组件
├── Json/                       # JSON 渲染组件
│   ├── JsonDataTable.php       # 数据表格
│   └── SchemaEditor.php        # Schema 编辑器
└── Common/                     # 通用组件
    └── TableColumnSetting.php  # 表格列设置
```

### Action 类型

```
Schema/Actions/
├── ActionInterface.php         # Action 接口
├── SetAction.php               # 设置状态值
├── CallAction.php              # 调用方法
├── FetchAction.php             # API 请求
├── IfAction.php                # 条件判断
├── ScriptAction.php            # 执行脚本
├── EmitAction.php              # 触发事件
├── CopyAction.php              # 复制到剪贴板
└── WebSocketAction.php         # WebSocket 操作
```

### Component 基类方法

```php
->props(array $props)           // 设置属性
->children(array|string $c)     // 设置子节点
->on(string $event, $handler)   // 绑定事件
->slot(string $name, array $c)  // 设置插槽
->if(string $expr)              // 条件渲染 v-if
->show(string $expr)            // 条件显示 v-show
->for(string $expr, ?string $key) // 循环渲染 v-for
->model(string|array $model)    // 双向绑定 v-model
->data(array $data)             // 响应式数据
->computed(array $computed)     // 计算属性
->methods(array $methods)       // 方法定义
->onMounted($actions)           // 挂载回调
->initApi(string|array $config) // 初始化 API
->uiApi(string|array $config)   // 动态 UI API
```

### CrudPage 方法

```php
CrudPage::make('标题')
    ->apiPrefix('/api/path')        // API 前缀
    ->apiParams(['key' => 'value']) // 额外 API 参数
    ->columns([...])                // 表格列配置
    ->scrollX(1200)                 // 表格横向滚动宽度
    ->pagination(true)              // 启用分页（默认 true）
    ->defaultPageSize(15)           // 默认每页条数
    ->tree('children', false)       // 树形模式
    ->search([...])                 // 搜索表单
    ->toolbarLeft([...])            // 左侧工具栏
    ->toolbarRight([...])           // 右侧工具栏
    ->data([...])                   // 额外数据
    ->methods([...])                // 额外方法
    ->modal('name', '标题', $form)  // 弹窗
    ->drawer('name', '标题', $form) // 抽屉
    ->build()                       // 构建 Schema
```

### OptForm 表单构建器

```php
OptForm::make('formData')
    ->fields([
        ['标题', 'title', Input::make()->props(['placeholder' => '请输入'])],
        ['状态', 'status', SwitchC::make(), true],  // 第4个参数为默认值
        ['分类', 'category', Select::make()->props(['options' => $options]), '', 'editingId'],  // 第5个参数为显示条件
    ])
    ->buttons([
        Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
        Button::make()->type('primary')->on('click', ['call' => 'handleSubmit'])->text('确定'),
    ])
```

## API 响应格式

统一响应格式：`{ code, msg, data }`

```php
// 成功响应
return success('操作成功', $data);  // { code: 0, msg: '操作成功', data: ... }
return success($data);              // { code: 0, msg: 'success', data: ... }

// 错误响应（抛出异常）
error('用户不存在', null, 40004);   // 抛出 ApiException
```

## CrudController action_type

CrudController 通过 `action_type` 参数路由不同操作：

### index 方法（GET 请求）

| action_type | 说明 |
|-------------|------|
| list（默认） | 列表数据（分页） |
| list_ui | 列表页面 Schema |
| form_ui | 表单页面 Schema |
| export | 导出数据 |
| batch_destroy | 批量删除 |

### update 方法（PUT 请求）

| action_type | 说明 |
|-------------|------|
| update（默认） | 更新记录 |
| status | 更新状态 |
| 自定义 | 子类可定义 `updateXxx` 方法处理 |

### destroy 方法（DELETE 请求）

| action_type | 说明 |
|-------------|------|
| delete（默认） | 删除记录 |
| batch | 批量删除 |

### CrudController 可重写方法

```php
// 配置方法
protected function getModelClass(): string;      // 必须实现
protected function getResourceName(): string;    // 资源名称
protected function getTable(): string;           // 数据表名
protected function getPrimaryKey(): string;      // 主键名
protected function getDefaultOrder(): array;     // 默认排序
protected function getDefaultPageSize(): int;    // 默认分页大小
protected function getListWith(): array;         // 列表预加载关联
protected function getShowWith(): array;         // 详情预加载关联
protected function getExportColumns(): array;    // 导出列配置
protected function getExportFilenamePrefix(): string; // 导出文件名前缀

// 查询方法
protected function applySearch(Builder $query, Request $request): void;  // 搜索条件
protected function applyFilters(Builder $query, Request $request): void; // 筛选条件

// 验证方法
protected function getStoreRules(): array;       // 创建验证规则
protected function getUpdateRules(int $id): array; // 更新验证规则

// 数据处理方法
protected function prepareStoreData(array $validated): array;  // 准备创建数据
protected function prepareUpdateData(array $validated): array; // 准备更新数据

// 回调方法
protected function afterStore(mixed $model, array $validated): void;
protected function afterUpdate(mixed $model, array $validated): void;
protected function afterStatusUpdate(mixed $model, bool $status): void;
protected function beforeDelete(mixed $model): void;
protected function afterDelete(mixed $model): void;

// UI Schema 方法
protected function listUi(): array;              // 列表页 Schema
protected function formUi(): array;              // 表单页 Schema
```

## 常用命令

```bash
# 安装 Lartrix
php artisan lartrix:install

# 发布前端资源
php artisan lartrix:publish-assets

# 卸载 Lartrix
php artisan lartrix:uninstall

# 创建模块
php artisan module:make ModuleName

# 运行测试
./vendor/bin/phpunit
```

## 开发规范

1. 控制器继承 `CrudController` 或 `Controller`
2. 使用 `success()` 和 `error()` 辅助函数
3. Schema 组件使用链式调用
4. NaiveUI 组件类名无 N 前缀
5. 使用 `$request->filled()` 检查非空参数
6. action_type 使用下划线格式：`list_ui`, `form_ui`, `reset_password`
