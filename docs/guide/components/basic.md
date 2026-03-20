# 基础组件

## Button 按钮

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Button;

// 基础按钮
Button::make('按钮文本');

// 主要按钮
Button::make('保存')->type('primary');

// 危险按钮
Button::make('删除')->type('error');

// 带图标
Button::make('新增')->props(['icon' => 'add']);

// 加载状态
Button::make('保存')
    $table->loading('{{ saving }}')
    ->on('click', [...]);
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| type | string | 类型：default, primary, info, success, warning, error |
| size | string | 尺寸：tiny, small, medium, large |
| disabled | boolean | 禁用 |
| loading | boolean | 加载状态 |
| icon | string | 图标 |
| block | boolean | 块级按钮 |
| ghost | boolean | 幽灵按钮 |
| dashed | boolean | 虚线边框 |
| round | boolean | 圆角 |
| circle | boolean | 圆形 |

## Input 输入框

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Input;

// 基础输入框
Input::make();

// 带占位符
Input::make()->props(['placeholder' => '请输入']);

// 可清空
Input::make()->props(['clearable' => true]);

// 密码输入
Input::make()->props(['type' => 'password']);

// 多行文本
Input::make()->props(['type' => 'textarea', 'rows' => 4]);

// 双向绑定
Input::make()->model('formData.name');
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| type | string | 类型：text, password, textarea, number |
| placeholder | string | 占位符 |
| clearable | boolean | 可清空 |
| disabled | boolean | 禁用 |
| readonly | boolean | 只读 |
| maxlength | number | 最大长度 |
| showCount | boolean | 显示字数统计 |
| rows | number | 行数（textarea）|

## Select 选择器

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Select;

// 基础选择器
Select::make()
    ->props([
        'options' => [
            ['label' => '选项1', 'value' => 1],
            ['label' => '选项2', 'value' => 2],
        ],
    ]);

// 使用 options 方法
Select::make()
    ->options([
        ['label' => '启用', 'value' => 1],
        ['label' => '禁用', 'value' => 0],
    ]);

// 多选
Select::make()
    ->options([...])
    ->props(['multiple' => true]);

// 可搜索
Select::make()
    ->options([...])
    ->props(['filterable' => true]);

// 双向绑定
Select::make()->options([...])->model('formData.status');
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| options | array | 选项列表 |
| multiple | boolean | 多选 |
| clearable | boolean | 可清空 |
| filterable | boolean | 可搜索 |
| placeholder | string | 占位符 |
| disabled | boolean | 禁用 |

## Switch 开关

```php
use Lartrix\\Schema\\Components\\NaiveUI\\SwitchC;

// 基础开关
SwitchC::make();

// 设定值
SwitchC::make()
    ->props([
        'checkedValue' => true,
        'uncheckedValue' => false,
    ]);

// 文字显示
SwitchC::make()
    ->props([
        'checkedText' => '启用',
        'uncheckedText' => '禁用',
    ]);

// 双向绑定
SwitchC::make()->model('formData.status');
```

## Card 卡片

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Card;

Card::make()
    ->title('卡片标题')
    ->children([
        // 卡片内容
        Input::make(),
        Button::make('保存'),
    ]);
```

### Props

| 属性 | 类型 | 说明 |
|------|------|------|
| title | string | 标题 |
| size | string | 尺寸：small, medium, large, huge |
| hoverable | boolean | 悬停效果 |
| embedded | boolean | 嵌入式 |
| segmented | boolean | 分段 |
