# FetchAction

FetchAction 用于发送 HTTP 请求，与后端 API 交互。

## 基础用法

```php
use Lartrix\\Schema\\Actions\\FetchAction;

// GET 请求
FetchAction::make('/api/users');

// POST 请求
FetchAction::make('/api/users')
    ->post()
    ->body(['name' => 'John']);

// PUT 请求
FetchAction::make('/api/users/1')
    ->put()
    ->body(['name' => 'Jane']);

// DELETE 请求
FetchAction::make('/api/users/1')->delete();
```

## HTTP 方法

| 方法 | 说明 |
|------|------|
| `get()` | GET 请求 |
| `post()` | POST 请求 |
| `put()` | PUT 请求 |
| `patch()` | PATCH 请求 |
| `delete()` | DELETE 请求 |

## 请求配置

### 请求体

```php
FetchAction::make('/api/users')
    ->post()
    ->body([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
```

### 请求头

```php
FetchAction::make('/api/users')
    ->get()
    $table->headers([
        'X-Custom-Header' => 'value',
    ]);
```

### URL 参数

```php
// 路径参数
FetchAction::make('/api/users/{{ userId }}');

// 查询参数（自动追加到 URL）
FetchAction::make('/api/users')
    ->get()
    $table->params([
        'page' => 1,
        'per_page' => 15,
    ]);
```

## 响应处理

### then - 成功回调

```php
FetchAction::make('/api/users')
    ->get()
    ->then([
        // 保存数据
        SetAction::make('users', '{{ $response.data }}'),
        // 显示成功消息
        CallAction::make('\$message.success', ['加载成功']),
    ]);
```

### catch - 失败回调

```php
FetchAction::make('/api/users')
    ->post()
    ->body(['name' => ''])
    ->then([...])
    ->catch([
        // 显示错误消息
        CallAction::make('\$message.error', ['{{ $response.message }}']),
    ]);
```

### finally - 最终回调

```php
FetchAction::make('/api/users')
    ->post()
    ->body([...])
    ->then([...])
    $table->finally([
        // 无论成功失败都执行
        SetAction::make('loading', false),
    ]);
```

## 完整示例

### 保存表单

```php
Button::make('保存')
    ->type('primary')
    ->on('click', [
        // 显示加载状态
        SetAction::make('saving', true),
        // 发送请求
        FetchAction::make('/api/posts')
            ->post()
            ->body([
                'title' => '{{ formData.title }}',
                'content' => '{{ formData.content }}',
            ])
            ->then([
                // 关闭弹窗
                SetAction::make('visible', false),
                // 重置表单
                SetAction::make('formData', ['title' => '', 'content' => '']),
                // 显示成功消息
                CallAction::make('\$message.success', ['保存成功']),
                // 刷新列表
                CallAction::make('loadData'),
            ])
            ->catch([
                CallAction::make('\$message.error', ['{{ $response.message }}']),
            ])
            $table->finally([
                SetAction::make('saving', false),
            ]),
    ]);
```

### 切换状态

```php
// 表格中的状态开关
[
    'key' => 'status',
    'title' => '状态',
    'slot' => [
        SwitchC::make()
            ->props(['value' => '{{ slotData.row.status }}'])
            ->on('update:value', 
                FetchAction::make('/api/posts/{{ slotData.row.id }}')
                    ->put()
                    ->body(['action_type' => 'status', 'status' => '{{ $event }}'])
                    ->then([
                        CallAction::make('\$message.success', ['状态更新成功']),
                    ])
            ),
    ],
]
```

## 输出格式

```php
FetchAction::make('/api/users')
    ->get()
    ->then([SetAction::make('list', '{{ $response.data }}')])
    $table->toArray();
```

输出：

```json
{
    "action": "fetch",
    "url": "/api/users",
    "method": "GET",
    "then": [
        {"action": "set", "name": "list", "value": "{{ $response.data }}"}
    ]
}
```
