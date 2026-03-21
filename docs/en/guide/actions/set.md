# SetAction

SetAction is used to set reactive data values.

## Basic Usage

```php
use Lartrix\Schema\Actions\SetAction;

SetAction::make('visible', true);
```

## Set Multiple Values

```php
SetAction::make([
    'visible' => true,
    'loading' => false,
    'formData.name' => 'John',
]);
```

## Use in Events

```php
Button::make('Open')
    ->on('click', SetAction::make('modalVisible', true));
```

## Nested Properties

```php
// Set nested property
SetAction::make('formData.user.name', 'John');

// Equivalent to:
// formData.user.name = 'John'
```

## Dynamic Values

```php
// Use expressions
SetAction::make('selectedId', '{{ row.id }}');

// Use event data
SetAction::make('inputValue', '{{ $event }}');
```

## Common Scenarios

### Toggle Modal

```php
Button::make('Open')
    ->on('click', SetAction::make('visible', true));

Button::make('Close')
    ->on('click', SetAction::make('visible', false));
```

### Reset Form

```php
Button::make('Reset')
    ->on('click', SetAction::make('formData', [
        'name' => '',
        'email' => '',
        'status' => true,
    ]));
```

### Set Loading State

```php
Button::make('Submit')
    ->on('click', [
        SetAction::make('loading', true),
        FetchAction::make('/api/submit')
            ->then([SetAction::make('loading', false)]),
    ]);
```
