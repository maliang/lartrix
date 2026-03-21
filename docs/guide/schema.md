# Schema 系统

Schema 系统是 Lartrix 的核心，让你用 PHP 代码描述界面，自动生成兼容 vschema-ui 的 JSON。

## 基础概念

### 什么是 Schema？

Schema 是一个描述界面结构和行为的声明式配置，由 PHP 对象树构建，最终序列化为 JSON。

```php
$button = Button::make('提交')
    ->type('primary')
    ->props(['size' => 'large']);

$json = $button->toArray();
// 结果:
// {
//     "com": "NButton",
//     "props": { "type": "primary", "size": "large" },
//     "text": "提交"
// }
```

### 组件基类

所有组件都继承自 `Component` 基类，提供通用方法：

| 方法 | 说明 |
|------|------|
| `props(array)` | 设置组件属性 |
| `children(array)` | 设置子组件 |
| `on(string, array)` | 绑定事件 |
| `slot(string, array)` | 定义插槽 |
| `if(string)` | v-if 条件 |
| `show(string)` | v-show 条件 |
| `for(string, ?string)` | v-for 循环 |
| `model(string)` | v-model 绑定 |

## 组件分类

### NaiveUI 组件

基础 UI 组件，对应 NaiveUI 的组件：

```php
use Lartrix\Schema\Components\NaiveUI\{Button, Input, Select, Card};

$card = Card::make()
    ->title('卡片标题')
    ->children([
        Input::make()
            ->props(['placeholder' => '请输入'])
            ->model('formData.name'),
        Button::make('保存')
            ->type('primary'),
    ]);
```

### 业务组件

专为后台管理设计的组件：

```php
use Lartrix\Schema\Components\Business\CrudPage;

$page = CrudPage::make('用户管理')
    ->apiPrefix('/users')
    ->columns([...])
    ->search([...]);
```

### 自定义组件

扩展自定义组件：

```php
use Lartrix\Schema\Components\Custom\VueECharts;

$chart = VueECharts::make()
    ->props(['option' => [...]]);
```

## Actions

Action 描述用户交互行为：

### 基础 Actions

```php
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};

// 设置状态
SetAction::make('visible', true);

// 调用方法
CallAction::make('$message.success', ['操作成功']);

// HTTP 请求
FetchAction::make('/api/users')
    ->get()
    ->then([CallAction::make('loadData')]);
```

### Action 链式调用

```php
FetchAction::make('/users/{id}')
    ->put()
    ->body(['status' => '{status}'])
    ->then([
        CallAction::make('$message.success', ['更新成功']),
        CallAction::make('loadData'),
    ])
    ->catch([
        CallAction::make('$message.error', ['更新失败']),
    ]);
```

## 响应式数据

在 CrudPage 等组件中定义响应式数据：

```php
CrudPage::make('标题')
    ->data([
        'formVisible' => false,
        'formData' => ['name' => '', 'status' => true],
    ])
    ->methods([
        'handleSubmit' => [...],
    ]);
```

## 最佳实践

1. **组件组合**：将复杂界面拆分为小组件
2. **复用 Schema**：提取公共部分为方法
3. **类型安全**：使用 PHP 8.1+ 类型声明
4. **表达式简化**：避免过度复杂的表达式
