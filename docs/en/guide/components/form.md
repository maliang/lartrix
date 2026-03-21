# Form Components

## Form

```php
use Lartrix\Schema\Components\NaiveUI\{Form, FormItem};

Form::make()
    ->model('formData')
    ->rules([
        'name' => ['required' => true, 'message' => 'Please enter name'],
        'email' => ['required' => true, 'type' => 'email'],
    ])
    ->children([
        FormItem::make('Name', 'name')
            ->child(Input::make()),
        FormItem::make('Email', 'email')
            ->child(Input::make()),
    ]);
```

## FormItem

```php
FormItem::make('Label', 'field_name')
    ->child(Input::make());

// Required field
FormItem::make('Name', 'name')
    ->required()
    ->child(Input::make());
```

## Checkbox

```php
use Lartrix\Schema\Components\NaiveUI\Checkbox;

// Single checkbox
Checkbox::make()
    ->props(['label' => 'Agree to terms'])
    ->model('formData.agree');

// Checkbox group
Checkbox::make()
    ->props([
        'options' => [
            ['label' => 'Option 1', 'value' => 1],
            ['label' => 'Option 2', 'value' => 2],
        ],
    ])
    ->model('formData.hobbies');
```

## Radio

```php
use Lartrix\Schema\Components\NaiveUI\Radio;

Radio::make()
    ->props([
        'options' => [
            ['label' => 'Male', 'value' => 'male'],
            ['label' => 'Female', 'value' => 'female'],
        ],
    ])
    ->model('formData.gender');
```

## DatePicker

```php
use Lartrix\Schema\Components\NaiveUI\DatePicker;

// Date picker
DatePicker::make()
    ->props(['type' => 'date'])
    ->model('formData.date');

// DateTime picker
DatePicker::make()
    ->props(['type' => 'datetime'])
    ->model('formData.datetime');

// Date range
DatePicker::make()
    ->props(['type' => 'daterange'])
    ->model('formData.dateRange');
```

## TimePicker

```php
use Lartrix\Schema\Components\NaiveUI\TimePicker;

TimePicker::make()
    ->model('formData.time');
```

## Upload

```php
use Lartrix\Schema\Components\NaiveUI\Upload;

Upload::make()
    ->props([
        'action' => '/api/upload',
        'accept' => 'image/*',
        'multiple' => true,
    ]);
```
