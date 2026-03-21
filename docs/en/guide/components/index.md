# Component Overview

Lartrix provides 120+ components covering various admin development scenarios.

## Component Categories

| Category | Description | Count |
|----------|-------------|-------|
| NaiveUI | Basic UI components | 100+ |
| Business | Business components | 10+ |
| Common | Common components | 10+ |
| Custom | Custom components | 10+ |
| Json | JSON components | 2 |

## Naming Convention

### NaiveUI Components

PHP class names have no N prefix, but output retains N prefix:

| PHP Class | Output Component |
|-----------|------------------|
| `Button::make()` | `NButton` |
| `Input::make()` | `NInput` |
| `SwitchC::make()` | `NSwitch` |
| `EmptyState::make()` | `NEmpty` |

::: tip SwitchC Explanation
`Switch` is a PHP reserved keyword, so we use `SwitchC`.
:::

## Basic Usage

```php
use Lartrix\Schema\Components\NaiveUI\Button;

$button = Button::make('Click Me')
    ->type('primary')
    ->props(['size' => 'large'])
    ->on('click', SetAction::make('visible', true));
```

## Method Chaining

All components support method chaining:

```php
Card::make()
    ->title('Card Title')
    ->children([
        Input::make()
            ->props(['placeholder' => 'Enter text'])
            ->model('form.name'),
        Space::make()
            ->children([
                Button::make('Cancel'),
                Button::make('Save')->type('primary'),
            ]),
    ]);
```

## Detailed Documentation

- [Basic Components](/en/guide/components/basic) - Button, Input, Select, etc.
- [Form Components](/en/guide/components/form) - Form, Checkbox, DatePicker, etc.
- [Data Display](/en/guide/components/data) - Table, List, Tree, etc.
- [Layout Components](/en/guide/components/layout) - Grid, Space, Layout, etc.
- [Feedback Components](/en/guide/components/feedback) - Alert, Message, Modal, etc.
- [Business Components](/en/guide/components/business) - CrudPage, OptForm, etc.
