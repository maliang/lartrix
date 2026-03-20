# 业务组件

## CrudPage

CrudPage 是构建 CRUD 页面的核心组件，集成了数据表格、搜索、工具栏等功能。

### 基础用法

```php
use Lartrix\\Schema\\Components\\Business\\CrudPage;

\$schema = CrudPage::make('用户管理')
    ->apiPrefix('/users')
    ->columns([
        ['key' => 'id', 'title' => 'ID'],
        ['key' => 'name', 'title' => '姓名'],
        ['key' => 'email', 'title' => '邮箱'],
    ]);

return success(\$schema->build());
```

### 完整配置

```php
CrudPage::make('用户管理')
    // API 配置
    ->apiPrefix('/users')           // API 前缀
    $table->apiParams(['type' => 'admin']) // 默认参数
    
    // 表格列
    ->columns([
        ['key' => 'id', 'title' => 'ID', 'width' => 80],
        ['key' => 'name', 'title' => '姓名'],
        ['key' => 'status', 'title' => '状态', 'slot' => [...]],
    ])
    
    // 搜索
    ->search([
        ['关键词', 'keyword', Input::make()],
        ['状态', 'status', Select::make()->options([...])],
    ])
    
    // 工具栏
    ->toolbarLeft([
        Button::make('新增')->type('primary'),
    ])
    ->toolbarRight([
        Button::make('导出'),
    ])
    
    // 分页
    $table->pagination(true)
    $table->defaultPageSize(20)
    
    // 滚动
    $table->scrollX(1200)
    
    // 树形数据
    $table->tree('children', false)
    
    // 数据和方法
    ->data(['formVisible' => false])
    ->methods(['handleSubmit' => [...]]);
```

## OptForm

OptForm 是操作表单组件，用于新增/编辑表单。

### 基础用法

```php
use Lartrix\\Schema\\Components\\Business\\OptForm;

\$form = OptForm::make('formData')
    $table->fields([
        ['名称', 'name', Input::make()->props(['placeholder' => '请输入名称'])],
        ['邮箱', 'email', Input::make()->props(['type' => 'email'])],
        ['状态', 'status', SwitchC::make(), true], // 默认值
    ])
    $table->buttons([
        Button::make('取消')
            ->on('click', SetAction::make('formVisible', false)),
        Button::make('保存')
            ->type('primary')
            ->on('click', CallAction::make('handleSubmit')),
    ]);
```

### 字段配置

```php
OptForm::make('formData')
    $table->fields([
        // [标签, 字段名, 组件, 默认值]
        ['名称', 'name', Input::make()],
        ['邮箱', 'email', Input::make(), ''],
        ['年龄', 'age', Input::make()->props(['type' => 'number']), 0],
        ['状态', 'status', SwitchC::make(), true],
    ]);
```

## DataTable

DataTable 是数据表格组件，用于展示数据列表。

```php
use Lartrix\\Schema\\Components\\Business\\DataTable;

DataTable::make()
    ->props([
        'data' => '{{ list }}',
        'columns' => [...],
        'pagination' => ['pageSize' => 20],
    ]);
```

## 编辑器组件

### MarkdownEditor

```php
use Lartrix\\Schema\\Components\\Business\\MarkdownEditor;

MarkdownEditor::make()
    ->model('formData.content')
    ->props([
        'height' => 400,
        'placeholder' => '请输入内容',
    ]);
```

### RichEditor

```php
use Lartrix\\Schema\\Components\\Business\\RichEditor;

RichEditor::make()
    ->model('formData.content')
    ->props([
        'height' => 400,
    ]);
```

## IconPicker

```php
use Lartrix\\Schema\\Components\\Business\\IconPicker;

IconPicker::make()
    ->model('formData.icon')
    ->props([
        'icons' => [...], // 图标列表
    ]);
```

## FlowEditor

```php
use Lartrix\\Schema\\Components\\Business\\FlowEditor;

FlowEditor::make()
    ->model('formData.flow')
    ->props([
        'nodes' => [...],
        'edges' => [...],
    ]);
```
