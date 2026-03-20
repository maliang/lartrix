# IfAction

IfAction 用于条件判断，根据不同条件执行不同的 Action。

## 基础用法

```php
use Lartrix\\Schema\\Actions\\IfAction;

IfAction::make('{{ formData.agree }}')
    $table->true([
        // 同意时执行
        FetchAction::make('/api/submit')->post(),
    ])
    $table->false([
        // 不同意时执行
        CallAction::make('\$message.warning', ['请先同意协议']),
    ]);
```

## 完整示例

### 表单验证

```php
Button::make('提交')
    ->on('click', [
        IfAction::make('{{ formData.name }}')
            $table->true([
                // 名称已填写，继续提交
                FetchAction::make('/api/users')
                    ->post()
                    ->body(['name' => '{{ formData.name }}']),
            ])
            $table->false([
                // 名称未填写，提示错误
                CallAction::make('\$message.error', ['请填写名称']),
            ]),
    ]);
```

### 权限检查

```php
Button::make('删除')
    ->type('error')
    ->on('click', [
        IfAction::make('{{ $user.canDelete }}')
            $table->true([
                CallAction::make('\$dialog.warning', [
                    '确认删除',
                    '删除后无法恢复',
                    [
                        'positiveText' => '确认删除',
                        'onPositiveClick' => [
                            FetchAction::make('/api/items/{{ id }}')->delete(),
                        ],
                    ],
                ]),
            ])
            $table->false([
                CallAction::make('\$message.error', ['无权限删除']),
            ]),
    ]);
```

### 复杂条件

```php
// 检查多个条件
IfAction::make('{{ formData.name && formData.email }}')
    $table->true([
        // 所有字段都已填写
        FetchAction::make('/api/register')->post(),
    ])
    $table->false([
        CallAction::make('\$message.warning', ['请填写完整信息']),
    ]);
```

## 嵌套使用

IfAction 可以嵌套使用：

```php
IfAction::make('{{ formData.type }}')
    $table->true([
        // 类型已选择
        IfAction::make('{{ formData.value }}')
            $table->true([
                // 值已填写，提交
                FetchAction::make('/api/submit')->post(),
            ])
            $table->false([
                CallAction::make('\$message.error', ['请填写值']),
            ]),
    ])
    $table->false([
        CallAction::make('\$message.error', ['请选择类型']),
    ]);
```

## 与逻辑运算符

支持在条件表达式中使用逻辑运算符：

| 运算符 | 说明 |
|--------|------|
| `&&` | 与 |
| `||` | 或 |
| `!` | 非 |
| `===` | 严格相等 |
| `!==` | 严格不相等 |

```php
// 与
IfAction::make('{{ formData.name && formData.email }}');

// 或
IfAction::make('{{ formData.phone || formData.email }}');

// 非
IfAction::make('{{ !formData.readonly }}');

// 严格相等
IfAction::make('{{ formData.status === "active" }}');
```

## 输出格式

```php
IfAction::make('{{ condition }}')
    $table->true([SetAction::make('result', true)])
    $table->false([SetAction::make('result', false)])
    $table->toArray();
```

输出：

```json
{
    "action": "if",
    "condition": "{{ condition }}",
    "true": [
        {"action": "set", "name": "result", "value": true}
    ],
    "false": [
        {"action": "set", "name": "result", "value": false}
    ]
}
```
