# SetAction

SetAction 用于设置响应式数据的状态。

## 基础用法

```php
use Lartrix\\Schema\\Actions\\SetAction;

// 设置单个值
SetAction::make('visible', true);

// 设置嵌套值
SetAction::make('form.name', 'John');

// 设置数组值
SetAction::make('list', [1, 2, 3]);
```

## 使用场景

### 控制弹窗显示

```php
Modal::make()
    ->show('{{ visible }}')
    ->on('update:show', SetAction::make('visible', '{{ $event }}'));

// 打开弹窗
Button::make('打开')
    ->on('click', SetAction::make('visible', true));
```

### 表单数据

```php
// 重置表单
Button::make('重置')
    ->on('click', SetAction::make('formData', [
        'name' => '',
        'email' => '',
        'status' => true,
    ]));
```

### 切换状态

```php
SwitchC::make()
    ->model('status')
    ->on('update:value', SetAction::make('status', '{{ $event }}'));
```

## 链式调用

SetAction 支持与其他 Action 组合：

```php
Button::make('保存')
    ->on('click', [
        SetAction::make('loading', true),
        FetchAction::make('/api/save')
            ->post()
            ->then([
                SetAction::make('loading', false),
                SetAction::make('visible', false),
            ]),
    ]);
```

## 输出格式

```php
SetAction::make('visible', true)->toArray();
```

输出：

```json
{
    "action": "set",
    "name": "visible",
    "value": true
}
```

对于嵌套属性：

```php
SetAction::make('form.name', 'John')->toArray();
```

输出：

```json
{
    "action": "set",
    "name": "form.name",
    "value": "John"
}
```
