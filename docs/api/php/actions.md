# Actions

## ActionInterface

Action 接口。

```php
Lartrix\\Schema\\Actions\\ActionInterface
```

## SetAction

设置状态。

```php
use Lartrix\\Schema\\Actions\\SetAction;

SetAction::make('name', 'value')
    $table->toArray();

// 输出
// ['action' => 'set', 'name' => 'name', 'value' => 'value']
```

### 参数

| 参数 | 类型 | 说明 |
|------|------|------|
| $name | string | 变量名，支持点号路径 |
| $value | mixed | 值 |

## CallAction

调用方法。

```php
use Lartrix\\Schema\\Actions\\CallAction;

CallAction::make('$message.success', ['参数1', '参数2'])
    $table->toArray();
```

### 内置方法

| 方法 | 说明 |
|------|------|
| `$message.success` | 成功消息 |
| `$message.error` | 错误消息 |
| `$message.warning` | 警告消息 |
| `$message.info` | 信息消息 |
| `$dialog.success` | 成功对话框 |
| `$dialog.error` | 错误对话框 |
| `$dialog.warning` | 警告对话框 |
| `$loadingBar.start` | 开始加载 |
| `$loadingBar.finish` | 完成加载 |
| `$loadingBar.error` | 加载错误 |
| `$nav.push` | 页面跳转 |
| `$nav.replace` | 替换页面 |
| `$nav.back` | 返回 |
| `$tab.close` | 关闭标签 |
| `$tab.open` | 打开标签 |
| `$window.open` | 新窗口 |
| `$download` | 下载文件 |

## FetchAction

HTTP 请求。

```php
use Lartrix\\Schema\\Actions\\FetchAction;

FetchAction::make('/api/users')
    ->get()
    $table->params(['page' => 1])
    ->then([...])
    ->catch([...]);
```

### HTTP 方法

| 方法 | 说明 |
|------|------|
| `get()` | GET 请求 |
| `post()` | POST 请求 |
| `put()` | PUT 请求 |
| `patch()` | PATCH 请求 |
| `delete()` | DELETE 请求 |

### 链式方法

| 方法 | 说明 |
|------|------|
| `body($data)` | 请求体 |
| `params($params)` | URL 参数 |
| `headers($headers)` | 请求头 |
| `then($actions)` | 成功回调 |
| `catch($actions)` | 失败回调 |
| `finally($actions)` | 最终回调 |

## IfAction

条件判断。

```php
use Lartrix\\Schema\\Actions\\IfAction;

IfAction::make('{{ condition }}')
    $table->true([...])
    $table->false([...]);
```

## ScriptAction

执行脚本。

```php
use Lartrix\\Schema\\Actions\\ScriptAction;

ScriptAction::make('console.log("debug")');
```

## EmitAction

触发事件。

```php
use Lartrix\\Schema\\Actions\\EmitAction;

EmitAction::make('event-name', ['参数']);
```

## CopyAction

复制到剪贴板。

```php
use Lartrix\\Schema\\Actions\\CopyAction;

CopyAction::make('要复制的文本');
```

## WebSocketAction

WebSocket 操作。

```php
use Lartrix\\Schema\\Actions\\WebSocketAction;

WebSocketAction::make('channel')
    $table->action('send')
    $table->payload(['data' => 'value']);
```
