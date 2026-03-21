# Basic Components

Basic UI components from NaiveUI.

## Button

```php
use Lartrix\Schema\Components\NaiveUI\Button;

Button::make('Click Me')
    ->type('primary')
    ->size('large')
    ->on('click', SetAction::make('visible', true));
```

Props: type (default, primary, info, success, warning, error), size (tiny, small, medium, large), disabled, loading

## Input

```php
use Lartrix\Schema\Components\NaiveUI\Input;

Input::make()
    ->props(['placeholder' => 'Enter text', 'clearable' => true])
    ->model('formData.name');
```

## Select

```php
use Lartrix\Schema\Components\NaiveUI\Select;

Select::make()
    ->props([
        'options' => [
            ['label' => 'Option 1', 'value' => 1],
            ['label' => 'Option 2', 'value' => 2],
        ],
    ])
    ->model('formData.status');
```
