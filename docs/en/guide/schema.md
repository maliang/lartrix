# Schema System

The Schema system is the core of Lartrix, allowing you to describe UI with PHP code and automatically generate vschema-ui compatible JSON.

## Basic Concepts

### What is Schema?

Schema is a declarative configuration describing UI structure and behavior, built from PHP object trees and serialized to JSON.

```php
$button = Button::make('Submit')
    ->type('primary')
    ->props(['size' => 'large']);

$json = $button->toArray();
// Result:
// {
//     "com": "NButton",
//     "props": { "type": "primary", "size": "large" },
//     "text": "Submit"
// }
```

### Component Base Class

All components inherit from `Component` base class with common methods:

| Method | Description |
|--------|-------------|
| `props(array)` | Set component properties |
| `children(array)` | Set child components |
| `on(string, array)` | Bind events |
| `slot(string, array)` | Define slots |
| `if(string)` | v-if condition |
| `show(string)` | v-show condition |
| `for(string, ?string)` | v-for loop |
| `model(string)` | v-model binding |

## Component Categories

### NaiveUI Components

Basic UI components corresponding to NaiveUI:

```php
use Lartrix\Schema\Components\NaiveUI\{Button, Input, Select, Card};

$card = Card::make()
    ->title('Card Title')
    ->children([
        Input::make()
            ->props(['placeholder' => 'Enter text'])
            ->model('formData.name'),
        Button::make('Save')
            ->type('primary'),
    ]);
```

### Business Components

Components designed for admin panels:

```php
use Lartrix\Schema\Components\Business\CrudPage;

$page = CrudPage::make('User Management')
    ->apiPrefix('/users')
    ->columns([...])
    ->search([...]);
```

### Custom Components

Extend with custom components:

```php
use Lartrix\Schema\Components\Custom\VueECharts;

$chart = VueECharts::make()
    ->props(['option' => [...]]);
```

## Actions

Actions describe user interaction behaviors:

### Basic Actions

```php
use Lartrix\Schema\Actions\{SetAction, CallAction, FetchAction};

// Set state
SetAction::make('visible', true);

// Call method
CallAction::make('$message.success', ['Success']);

// HTTP request
FetchAction::make('/api/users')
    ->get()
    ->then([CallAction::make('loadData')]);
```

### Action Chaining

```php
FetchAction::make('/users/{id}')
    ->put()
    ->body(['status' => '{status}'])
    ->then([
        CallAction::make('$message.success', ['Updated']),
        CallAction::make('loadData'),
    ])
    ->catch([
        CallAction::make('$message.error', ['Failed']),
    ]);
```

## Reactive Data

Define reactive data in components like CrudPage:

```php
CrudPage::make('Title')
    ->data([
        'formVisible' => false,
        'formData' => ['name' => '', 'status' => true],
    ])
    ->methods([
        'handleSubmit' => [...],
    ]);
```

## Best Practices

1. **Component Composition**: Break complex UI into small components
2. **Schema Reuse**: Extract common parts as methods
3. **Type Safety**: Use PHP 8.1+ type declarations
4. **Expression Simplification**: Avoid overly complex expressions
