# 表单组件

## Form 表单

```php
use Lartrix\\Schema\\Components\\NaiveUI\\{Form, FormItem};

Form::make()
    ->model('formData')
    $table->rules([
        'name' => ['required' => true, 'message' => '请输入名称'],
        'email' => ['required' => true, 'type' => 'email'],
    ])
    ->children([
        FormItem::make('名称', 'name')
            $table->child(Input::make()),
        FormItem::make('邮箱', 'email')
            $table->child(Input::make()),
    ]);
```

## FormItem 表单项

```php
FormItem::make('标签', '字段名')
    $table->child(Input::make());

// 必填项
FormItem::make('名称', 'name')
    $table->required()
    $table->child(Input::make());
```

## Checkbox 复选框

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Checkbox;

// 单个复选框
Checkbox::make()
    ->props(['label' => '同意协议'])
    ->model('formData.agree');

// 复选框组
Checkbox::make()
    ->props([
        'options' => [
            ['label' => '选项1', 'value' => 1],
            ['label' => '选项2', 'value' => 2],
        ],
    ])
    ->model('formData.hobbies');
```

## Radio 单选框

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Radio;

Radio::make()
    ->props([
        'options' => [
            ['label' => '男', 'value' => 'male'],
            ['label' => '女', 'value' => 'female'],
        ],
    ])
    ->model('formData.gender');
```

## DatePicker 日期选择

```php
use Lartrix\\Schema\\Components\\NaiveUI\\DatePicker;

// 日期选择
DatePicker::make()
    ->props(['type' => 'date'])
    ->model('formData.date');

// 日期时间
DatePicker::make()
    ->props(['type' => 'datetime'])
    ->model('formData.datetime');

// 日期范围
DatePicker::make()
    ->props(['type' => 'daterange'])
    ->model('formData.dateRange');
```

## TimePicker 时间选择

```php
use Lartrix\\Schema\\Components\\NaiveUI\\TimePicker;

TimePicker::make()
    ->model('formData.time');
```

## Upload 上传

```php
use Lartrix\\Schema\\Components\\NaiveUI\\Upload;

Upload::make()
    ->props([
        'action' => '/api/upload',
        'accept' => 'image/*',
        'multiple' => true,
    ]);
```
