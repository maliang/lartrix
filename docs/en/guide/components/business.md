# Business Components

## CrudPage

CrudPage is the core component for building CRUD pages, integrating data tables, search, toolbars, and more.

### Basic Usage

```php
use Lartrix\Schema\Components\Business\CrudPage;

$schema = CrudPage::make('User Management')
    ->apiPrefix('/users')
    ->columns([
        ['key' => 'id', 'title' => 'ID'],
        ['key' => 'name', 'title' => 'Name'],
        ['key' => 'email', 'title' => 'Email'],
    ]);

return success($schema->build());
```

### Complete Configuration

```php
CrudPage::make('User Management')
    // API configuration
    ->apiPrefix('/users')           // API prefix
    ->apiParams(['type' => 'admin']) // Default params
    
    // Table columns
    ->columns([
        ['key' => 'id', 'title' => 'ID', 'width' => 80],
        ['key' => 'name', 'title' => 'Name'],
        ['key' => 'status', 'title' => 'Status', 'slot' => [...]],
    ])
    
    // Search
    ->search([
        ['Keyword', 'keyword', Input::make()],
        ['Status', 'status', Select::make()->options([...])],
    ])
    
    // Toolbar
    ->toolbarLeft([
        Button::make('Add')->type('primary'),
    ])
    ->toolbarRight([
        Button::make('Export'),
    ])
    
    // Pagination
    ->pagination(true)
    ->defaultPageSize(20)
    
    // Scroll
    ->scrollX(1200)
    
    // Tree data
    ->tree('children', false)
    
    // Data and methods
    ->data(['formVisible' => false])
    ->methods(['handleSubmit' => [...]]);
```

## OptForm

OptForm is an operation form component for add/edit forms.

### Basic Usage

```php
use Lartrix\Schema\Components\Business\OptForm;

$form = OptForm::make('formData')
    ->fields([
        ['Name', 'name', Input::make()->props(['placeholder' => 'Enter name'])],
        ['Email', 'email', Input::make()->props(['type' => 'email'])],
        ['Status', 'status', SwitchC::make(), true], // Default value
    ])
    ->buttons([
        Button::make('Cancel')
            ->on('click', SetAction::make('formVisible', false)),
        Button::make('Save')
            ->type('primary')
            ->on('click', CallAction::make('handleSubmit')),
    ]);
```

### Field Configuration

```php
OptForm::make('formData')
    ->fields([
        // [Label, Field name, Component, Default value]
        ['Name', 'name', Input::make()],
        ['Email', 'email', Input::make(), ''],
        ['Age', 'age', Input::make()->props(['type' => 'number']), 0],
        ['Status', 'status', SwitchC::make(), true],
    ]);
```

## DataTable

DataTable is a data table component for displaying data lists.

```php
use Lartrix\Schema\Components\Business\DataTable;

DataTable::make()
    ->props([
        'data' => '{{ list }}',
        'columns' => [...],
        'pagination' => ['pageSize' => 20],
    ]);
```

## Editor Components

### MarkdownEditor

```php
use Lartrix\Schema\Components\Business\MarkdownEditor;

MarkdownEditor::make()
    ->model('formData.content')
    ->props([
        'height' => 400,
        'placeholder' => 'Enter content',
    ]);
```

### RichEditor

```php
use Lartrix\Schema\Components\Business\RichEditor;

RichEditor::make()
    ->model('formData.content')
    ->props([
        'height' => 400,
    ]);
```

## IconPicker

```php
use Lartrix\Schema\Components\Business\IconPicker;

IconPicker::make()
    ->model('formData.icon')
    ->props([
        'icons' => [...], // Icon list
    ]);
```

## FlowEditor

```php
use Lartrix\Schema\Components\Business\FlowEditor;

FlowEditor::make()
    ->model('formData.flow')
    ->props([
        'nodes' => [...],
        'edges' => [...],
    ]);
```
