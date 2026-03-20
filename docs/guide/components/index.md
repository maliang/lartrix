# 组件概述

Lartrix 提供 120+ 个组件，覆盖后台开发的各种场景。

## 组件分类

| 分类 | 说明 | 数量 |
|------|------|------|
| NaiveUI | 基础 UI 组件 | 100+ |
| Business | 业务组件 | 10+ |
| Common | 通用组件 | 10+ |
| Custom | 自定义组件 | 10+ |
| Json | JSON 组件 | 2 |

## 命名约定

### NaiveUI 组件

PHP 类名无 N 前缀，输出保留 N 前缀：

| PHP 类 | 输出组件 |
|--------|----------|
| `Button::make()` | `NButton` |
| `Input::make()` | `NInput` |
| `SwitchC::make()` | `NSwitch` |
| `EmptyState::make()` | `NEmpty` |

::: tip SwitchC 说明
`Switch` 是 PHP 保留字，所以使用 `SwitchC`。
:::

## 基础用法

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Button;

\$button = Button::make('点击我')
    ->type('primary')
    ->props(['size' => 'large'])
    ->on('click', SetAction::make('visible', true));
```

## 链式调用

所有组件都支持链式调用：

```php
Card::make()
    ->title('卡片标题')
    ->children([
        Input::make()
            ->props(['placeholder' => '请输入'])
            ->model('form.name'),
        Space::make()
            ->children([
                Button::make('取消'),
                Button::make('保存')->type('primary'),
            ]),
    ]);
```

## 查看详细文档

- [基础组件](/guide/components/basic) - Button, Input, Select 等
- [表单组件](/guide/components/form) - Form, Checkbox, DatePicker 等
- [数据展示](/guide/components/data) - Table, List, Tree 等
- [布局组件](/guide/components/layout) - Grid, Space, Layout 等
- [反馈组件](/guide/components/feedback) - Alert, Message, Modal 等
- [业务组件](/guide/components/business) - CrudPage, OptForm 等
