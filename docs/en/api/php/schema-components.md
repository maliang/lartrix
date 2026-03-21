# Schema Components

## Component

Base class for all components.

### Namespace

```php
Lartrix\Schema\Components\Component
```

### Common Methods

| Method | Parameters | Description |
|--------|------------|-------------|
| `make($text = null)` | string | Create component instance |
| `props(array $props)` | array | Set properties |
| `children($children)` | array\|string | Set child components |
| `on($event, $handler)` | string, array | Bind event |
| `slot($name, $content)` | string, array | Define slot |
| `if($condition)` | string | v-if condition |
| `show($condition)` | string | v-show condition |
| `for($expression, $key = null)` | string, string | v-for loop |
| `model($path)` | string\|array | v-model binding |
| `data(array $data)` | array | Set reactive data |
| `methods(array $methods)` | array | Set methods |
| `onMounted($actions)` | array | Mount callback |

### Conversion Methods

| Method | Description |
|--------|-------------|
| `toArray()` | Convert to array |
| `toJson()` | Convert to JSON |
| `build()` | Build complete schema |

## NaiveUI Components

### Button

Button component.

```php
use Lartrix\Schema\Components\NaiveUI\Button;

Button::make('Text')
    ->type('primary')
    ->size('large')
    ->on('click', [...]);
```

### Input

Input component.

```php
use Lartrix\Schema\Components\NaiveUI\Input;

Input::make()
    ->props(['placeholder' => 'Hint'])
    ->model('form.name');
```

### Select

Select component.

```php
use Lartrix\Schema\Components\NaiveUI\Select;

Select::make()
    ->options([
        ['label' => 'Option 1', 'value' => 1],
    ])
    ->model('form.status');
```

### SwitchC

Switch component (`Switch` is a PHP reserved word).

```php
use Lartrix\Schema\Components\NaiveUI\SwitchC;

SwitchC::make()
    ->props(['checkedValue' => true])
    ->model('form.status');
```

### Card

Card component.

```php
use Lartrix\Schema\Components\NaiveUI\Card;

Card::make()
    ->title('Title')
    ->children([...]);
```

### Form / FormItem

Form components.

```php
use Lartrix\Schema\Components\NaiveUI\{Form, FormItem};

Form::make()
    ->model('formData')
    ->children([
        FormItem::make('Label', 'name')
            ->child(Input::make()),
    ]);
```

### Modal

Modal dialog component.

```php
use Lartrix\Schema\Components\NaiveUI\Modal;

Modal::make()
    ->show('visible')
    ->on('update:show', SetAction::make('visible', '{{ $event }}'))
    ->children([...]);
```

### Drawer

Drawer component.

```php
use Lartrix\Schema\Components\NaiveUI\Drawer;

Drawer::make()
    ->show('drawerVisible')
    ->props(['title' => 'Details'])
    ->children([...]);
```

### Table

Table component.

```php
use Lartrix\Schema\Components\NaiveUI\Table;

Table::make()
    ->props([
        'data' => '{{ list }}',
        'columns' => [...],
    ]);
```

## Business Components

### CrudPage

CRUD page component.

```php
use Lartrix\Schema\Components\Business\CrudPage;

CrudPage::make('Title')
    ->apiPrefix('/api/users')
    ->columns([...])
    ->search([...])
    ->toolbarLeft([...]);
```

### OptForm

Operation form component.

```php
use Lartrix\Schema\Components\Business\OptForm;

OptForm::make('formData')
    ->fields([
        ['Label', 'name', Input::make()],
    ])
    ->buttons([...]);
```

### MarkdownEditor

Markdown editor.

```php
use Lartrix\Schema\Components\Business\MarkdownEditor;

MarkdownEditor::make()
    ->model('form.content');
```

### RichEditor

Rich text editor.

```php
use Lartrix\Schema\Components\Business\RichEditor;

RichEditor::make()
    ->model('form.content');
```
