# Action 概述

Action 是 Lartrix 中描述用户交互行为的抽象，让你用 PHP 代码定义前端逻辑。

## 什么是 Action？

Action 是一个描述"当用户做某事时应该发生什么"的配置对象：

```php
// 用户点击按钮时
Button::make('保存')
    ->on('click', [
        // 设置 loading 状态
        SetAction::make('saving', true),
        // 发送保存请求
        FetchAction::make('/api/posts')
            ->post()
            ->body(['title' => '{{ form.title }}'])
            ->then([
                SetAction::make('saving', false),
                CallAction::make('$message.success', ['保存成功']),
                SetAction::make('visible', false),
            ]),
    ]);
```

## Action 类型

| Action | 说明 |
|--------|------|
| `SetAction` | 设置响应式数据 |
| `CallAction` | 调用内置或自定义方法 |
| `FetchAction` | 发送 HTTP 请求 |
| `IfAction` | 条件判断 |
| `ScriptAction` | 执行脚本 |
| `EmitAction` | 触发自定义事件 |
| `CopyAction` | 复制到剪贴板 |
| `WebSocketAction` | WebSocket 操作 |

## 链式调用

大多数 Action 支持链式调用：

```php
FetchAction::make('/api/users/{id}')
    ->put()                           // HTTP 方法
    ->body(['status' => true])       // 请求体
    ->headers(['X-Custom' => 'value']) // 请求头
    ->then([...])                     // 成功回调
    ->catch([...]);                   // 失败回调
```

## 查看详细文档

- [SetAction](/guide/actions/set) - 设置状态
- [CallAction](/guide/actions/call) - 调用方法
- [FetchAction](/guide/actions/fetch) - HTTP 请求
- [IfAction](/guide/actions/if) - 条件判断
- [其他 Actions](/guide/actions/others) - Script, Emit, Copy 等
