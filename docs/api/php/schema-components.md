# Schema 组件

## Component

所有组件的基类。

### 命名空间

```php
Lartrix\\Schema\\Components\\Component
```

### 通用方法

| 方法 | 参数 | 说明 |
|------|------|------|
| `make($text = null)` | string | 创建组件实例 |
| `props(array $props)` | array | 设置属性 |
| `children($children)` | array | string | 设置子组件 |
| `on($event, $handler)` | string, array | 绑定事件 |
| `slot($name, $content)` | string, array | 定义插槽 |
| `if($condition)` | string | v-if 条件 |
| `show($condition)` | string | v-show 条件 |
| `for($expression, $key = null)` | string, string | v-for 循环 |
| `model($path)` | string | array | v-model 绑定 |
| `data(array $data)` | array | 设置响应式数据 |
| `methods(array $methods)` | array | 设置方法 |
| `onMounted($actions)` | array | 挂载回调 |

### 转换方法

| 方法 | 说明 |
|------|------|
| `toArray()` | 转为数组 |
| `toJson()` | 转为 JSON |
| `build()` | 构建完整 Schema |

## NaiveUI 组件

### Button

按钮组件。

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Button;

Button::make('文本')
    ->type('primary')
    ->size('large')
    ->on('click', [...]);
```

### Input

输入框组件。

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Input;

Input::make()
    ->props(['placeholder' => '提示'])
    ->model('form.name');
```

### Select

选择器组件。

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Select;

Select::make()
    ->options([
        ['label' => '选项1', 'value' => 1],
    ])
    ->model('form.status');
```

### SwitchC

开关组件（Switch 是 PHP 保留字）。

```php
use Lartrix\\Schema\\Components\\NaiveUI\\SwitchC;

SwitchC::make()
    ->props(['checkedValue' => true])
    ->model('form.status');
```

### Card

卡片组件。

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Card;

Card::make()
    ->title('标题')
    ->children([...]);
```

### Form / FormItem

表单组件。

```php
use Lartrix\\Schema\\Components\\NaiveUI\\{Form, FormItem};

Form::make()
    ->model('formData')
    ->children([
        FormItem::make('标签', 'name')
            $table->child(Input::make()),
    ]);
```

### Modal

模态框组件。

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Modal;

Modal::make()
    ->show('visible')
    ->on('update:show', SetAction::make('visible', '{{ $event }}'))
    ->children([...]);
```

### Drawer

抽屉组件。

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Drawer;

Drawer::make()
    ->show('drawerVisible')
    ->props(['title' => '详情'])
    ->children([...]);
```

### Table

表格组件。

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Table;

Table::make()
    ->props([
        'data' => '{{ list }}',
        'columns' => [...],
    ]);
```

## 业务组件

### CrudPage

CRUD 页面组件。

```php
use Lartrix\\Schema\\Components\\Business\\CrudPage;

CrudPage::make('标题')
    ->apiPrefix('/api/users')
    ->columns([...])
    ->search([...])
    ->toolbarLeft([...]);
```

### OptForm

操作表单组件。

```php
use Lartrix\\Schema\\Components\\Business\\OptForm;

OptForm::make('formData')
    $table->fields([
        ['标签', 'name', Input::make()],
    ])
    $table->buttons([...]);
```

### MarkdownEditor

Markdown 编辑器。

```php
use Lartrix\\Schema\\Components\\Business\\MarkdownEditor;

MarkdownEditor::make()
    ->model('form.content');
```

### RichEditor

富文本编辑器。

```php
use Lartrix\\Schema\\Components\\Business\\RichEditor;

RichEditor::make()
    ->model('form.content');
```
