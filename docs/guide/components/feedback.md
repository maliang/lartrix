# 反馈组件

## Alert 警告提示

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Alert;

// 信息提示
Alert::make()
    ->type('info')
    ->props(['title' => '提示', 'content' => '这是一条信息提示']);

// 成功提示
Alert::make()
    ->type('success')
    ->props(['title' => '成功', 'content' => '操作成功完成']);

// 警告提示
Alert::make()
    ->type('warning')
    ->props(['title' => '警告', 'content' => '请谨慎操作']);

// 错误提示
Alert::make()
    ->type('error')
    ->props(['title' => '错误', 'content' => '操作失败']);

// 可关闭
Alert::make()
    ->type('info')
    ->props(['closable' => true]);
```

## Message 消息提示

通过 CallAction 调用：

```php
CallAction::make('\$message.success', ['操作成功']);
CallAction::make('\$message.error', ['操作失败']);
CallAction::make('\$message.warning', ['警告信息']);
CallAction::make('\$message.info', ['提示信息']);

// 带选项
CallAction::make('\$message.success', [
    '保存成功',
    ['duration' => 5000, 'closable' => true],
]);
```

## Dialog 对话框

通过 CallAction 调用：

```php
// 成功对话框
CallAction::make('\$dialog.success', [
    '操作成功',
    '数据已保存',
]);

// 确认对话框
CallAction::make('\$dialog.warning', [
    '确认删除',
    '此操作不可恢复，是否继续？',
    [
        'positiveText' => '确认删除',
        'negativeText' => '取消',
        'onPositiveClick' => [
            // 确认后执行
            FetchAction::make('/api/delete')->delete(),
        ],
        'onNegativeClick' => [
            // 取消后执行
            CallAction::make('\$message.info', ['已取消']),
        ],
    ],
]);
```

## Modal 模态框

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Modal;

Modal::make()
    ->show('{{ visible }}')
    ->props([
        'title' => '标题',
        'preset' => 'card',
    ])
    ->on('update:show', SetAction::make('visible', '{{ $event }}'))
    ->children([
        // 模态框内容
        Input::make(),
    ]);
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| show | boolean | 显示状态 |
| title | string | 标题 |
| preset | string | 预设：dialog, card |
| closable | boolean | 显示关闭按钮 |
| maskClosable | boolean | 点击遮罩关闭 |
| closeOnEsc | boolean | ESC 关闭 |
| autoFocus | boolean | 自动聚焦 |
| transformOrigin | string | 动画原点 |

## Drawer 抽屉

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Drawer;

Drawer::make()
    ->show('{{ drawerVisible }}')
    ->props([
        'title' => '详情',
        'width' => 400,
        'placement' => 'right', // left, right, top, bottom
    ])
    ->on('update:show', SetAction::make('drawerVisible', '{{ $event }}'))
    ->children([
        // 抽屉内容
    ]);
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| show | boolean | 显示状态 |
| title | string | 标题 |
| placement | string | 位置：left, right, top, bottom |
| width | number/string | 宽度 |
| height | number/string | 高度 |
| closable | boolean | 显示关闭按钮 |
| maskClosable | boolean | 点击遮罩关闭 |

## Popconfirm 气泡确认

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Popconfirm;

Popconfirm::make()
    ->props([
        'positiveText' => '确认',
        'negativeText' => '取消',
    ])
    ->on('positiveClick', FetchAction::make('/api/delete')->delete())
    ->children([
        Button::make('删除')->type('error'),
    ]);
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| positiveText | string | 确认按钮文本 |
| negativeText | string | 取消按钮文本 |
| showIcon | boolean | 显示图标 |
| onPositiveClick | array | 确认回调 |
| onNegativeClick | array | 取消回调 |

## Tooltip 文字提示

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Tooltip;

Tooltip::make()
    ->props(['trigger' => 'hover'])
    ->children([
        Button::make('悬停查看'),
    ]);
```

## LoadingBar 加载条

通过 CallAction 调用：

```php
// 开始加载
CallAction::make('\$loadingBar.start');

// 完成加载
CallAction::make('\$loadingBar.finish');

// 加载错误
CallAction::make('\$loadingBar.error');
```
