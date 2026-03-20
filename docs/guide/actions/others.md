# 其他 Actions

## ScriptAction

执行自定义 JavaScript 代码。

```php
use Lartrix\\Schema\\Actions\\ScriptAction;

ScriptAction::make('
    console.log("Debug:", this.formData);
    this.formData.name = this.formData.name.toUpperCase();
');
```

::: danger 谨慎使用
ScriptAction 允许执行任意 JavaScript 代码，这会破坏声明式编程的优势。

**何时可以使用：**
- 需要访问浏览器 API（如 localStorage、console）
- 需要执行复杂的数据转换逻辑
- 临时调试代码

**安全风险：**
- 可能引入 XSS 漏洞
- 难以维护和测试
- 破坏组件的可预测性

**推荐替代方案：**
- 使用 SetAction 更新状态
- 使用 CallAction 调用预定义方法
- 使用 FetchAction 与后端交互
- 在后端处理复杂逻辑

**如果必须使用，请确保：**
- 不要在 script 中处理用户输入
- 不要在 script 中存储敏感信息
- 添加详细的代码注释
:::

## EmitAction

触发自定义事件，用于组件间通信。

```php
use Lartrix\\Schema\\Actions\\EmitAction;

// 触发自定义事件
EmitAction::make('refresh-list');

// 带参数
EmitAction::make('item-selected', ['{{ item.id }}']);
```

在父组件中监听：

```php
CrudPage::make('标题')
    ->on('refresh-list', [
        CallAction::make('loadData'),
    ]);
```

## CopyAction

复制文本到剪贴板。

```php
use Lartrix\\Schema\\Actions\\CopyAction;

Button::make('复制链接')
    ->on('click', [
        CopyAction::make('https://example.com/item/{{ id }}'),
        CallAction::make('\$message.success', ['链接已复制']),
    ]);
```

## WebSocketAction

发送 WebSocket 消息。

```php
use Lartrix\\Schema\\Actions\\WebSocketAction;

WebSocketAction::make('chat-channel')
    ->action('send-message')
    ->payload(['text' => '{{ message }}']);
```

## Action 组合

在实际应用中，经常需要组合多个 Action：

### 删除确认流程

```php
Button::make('删除')
    ->type('error')
    ->on('click', [
        // 显示确认对话框
        CallAction::make('\$dialog.warning', [
            '确认删除',
            '此操作不可恢复',
            [
                'positiveText' => '确认删除',
                'negativeText' => '取消',
                'onPositiveClick' => [
                    // 显示加载状态
                    SetAction::make('deleting', true),
                    // 发送删除请求
                    FetchAction::make('/api/items/{{ slotData.row.id }}')
                        ->delete()
                        ->then([
                            CallAction::make('\$message.success', ['删除成功']),
                            CallAction::make('loadData'),
                        ])
                        ->catch([
                            CallAction::make('\$message.error', ['删除失败']),
                        ])
                        ->finally([
                            SetAction::make('deleting', false),
                        ]),
                ],
            ],
        ]),
    ]);
```

### 表单提交流程

```php
Button::make('提交')
    ->type('primary')
    ->on('click', [
        // 验证
        IfAction::make('{{ formData.title && formData.content }}')
            ->true([
                SetAction::make('submitting', true),
                FetchAction::make('/api/posts')
                    ->post()
                    ->body(['title' => '{{ formData.title }}', 'content' => '{{ formData.content }}'])
                    ->then([
                        SetAction::make('visible', false),
                        SetAction::make('formData', ['title' => '', 'content' => '']),
                        CallAction::make('\$message.success', ['发布成功']),
                        CallAction::make('loadData'),
                    ])
                    ->finally([
                        SetAction::make('submitting', false),
                    ]),
            ])
            ->false([
                CallAction::make('\$message.error', ['请填写标题和内容']),
            ]),
    ]);
```
