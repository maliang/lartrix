# CallAction

CallAction 用于调用内置方法或自定义方法。

## 基础用法

```php
use Lartrix\Schema\Actions\CallAction;

// 调用消息提示
CallAction::make('$message.success', ['操作成功']);

// 调用对话框
CallAction::make('$dialog.success', ['标题', '内容']);

// 调用导航
CallAction::make('$nav.push', ['/users']);
```

## 内置方法

### 消息提示

```php
// 成功
CallAction::make('$message.success', ['保存成功']);
CallAction::make('$message.success', ['标题', '保存成功']);

// 错误
CallAction::make('$message.error', ['操作失败']);

// 警告
CallAction::make('$message.warning', ['警告信息']);

// 信息
CallAction::make('$message.info', ['提示信息']);
```

### 对话框

```php
// 确认对话框
CallAction::make('$dialog.success', [
    '操作成功',
    '数据已保存',
    [
        'positiveText' => '确定',
        'onPositiveClick' => [...], // 确认后的 Action
    ]
]);

// 错误对话框
CallAction::make('$dialog.error', ['错误', '操作失败']);
```

### 加载条

```php
// 开始加载
CallAction::make('$loadingBar.start');

// 完成加载
CallAction::make('$loadingBar.finish');

// 加载错误
CallAction::make('$loadingBar.error');
```

### 页面导航

```php
// 跳转页面
CallAction::make('$nav.push', ['/users']);

// 替换页面
CallAction::make('$nav.replace', ['/users']);

// 返回
CallAction::make('$nav.back');
```

### 标签页操作

```php
// 关闭当前标签
CallAction::make('$tab.close');

// 打开标签
CallAction::make('$tab.open', ['/users', '用户管理']);

// 固定标签
CallAction::make('$tab.fix', ['/users']);
```

### 新窗口

```php
// 打开新窗口
CallAction::make('$window.open', ['https://example.com']);

// 下载文件
CallAction::make('$download', ['/api/export', 'data.xlsx']);
```

## 调用自定义方法

定义在 CrudPage 的方法：

```php
CrudPage::make('标题')
    ->methods([
        'handleSubmit' => [
            FetchAction::make('/api/save')
                ->post()
                ->then([CallAction::make('$message.success', ['保存成功'])]),
        ],
        'loadData' => [
            FetchAction::make('/api/list')
                ->get()
                ->then([SetAction::make('list', '{{ $response.data }}')]),
        ],
    ])
    ->children([
        Button::make('保存')
            ->on('click', CallAction::make('handleSubmit')),
        Button::make('刷新')
            ->on('click', CallAction::make('loadData')),
    ]);
```

## 简写形式

以下写法等效：

```php
// 简写
CallAction::make('$message.success', ['成功']);

// 完整
CallAction::make('$methods.$message.success', ['成功']);
```

## 输出格式

```php
CallAction::make('$message.success', ['操作成功'])->toArray();
```

输出：

```json
{
    "action": "call",
    "method": "$message.success",
    "args": ["操作成功"]
}
```
