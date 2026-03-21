# Examples

Complete Lartrix usage examples to help you get started quickly.

## Example List

- [User Management](/en/examples/users) - Complete user CRUD
- [Post Management](/en/examples/posts) - Post management with rich text editor
- [Product Management](/en/examples/products) - Product management with image upload
- [Category Management](/en/examples/categories) - Tree-based category management
- [Full Module Example](/en/examples/full-module) - Create a complete module from scratch

## Quick Preview

### Simplest CRUD

```php
class UserController extends CrudController
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function listUi(): array
    {
        return success(
            CrudPage::make('User Management')
                ->apiPrefix('/users')
                ->columns([
                    ['key' => 'id', 'title' => 'ID'],
                    ['key' => 'name', 'title' => 'Name'],
                    ['key' => 'email', 'title' => 'Email'],
                ])
                ->build()
        );
    }
}
```

### With Search and Filters

```php
protected function listUi(): array
{
    $schema = CrudPage::make('Post Management')
        ->apiPrefix('/posts')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'title', 'title' => 'Title'],
            ['key' => 'status', 'title' => 'Status'],
        ])
        ->search([
            ['Title', 'title', Input::make()],
            ['Status', 'status', Select::make()->options([
                ['label' => 'Active', 'value' => 1],
                ['label' => 'Inactive', 'value' => 0],
            ])],
        ]);

    return success($schema->build());
}
```

### With Action Buttons

```php
protected function listUi(): array
{
    $schema = CrudPage::make('User Management')
        ->apiPrefix('/users')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'name', 'title' => 'Name'],
            [
                'key' => 'actions',
                'title' => 'Actions',
                'slot' => [
                    Space::make()
                        ->children([
                            Button::make('Edit')
                                ->type('primary')
                                ->size('small')
                                ->on('click', SetAction::make('editId', '{{ slotData.row.id }}')),
                            Button::make('Delete')
                                ->type('error')
                                ->size('small')
                                ->on('click', [
                                    CallAction::make('$dialog.warning', [
                                        'Confirm Delete',
                                        'This action cannot be undone',
                                        [
                                            'positiveText' => 'Confirm',
                                            'onPositiveClick' => [
                                                FetchAction::make('/users/{{ slotData.row.id }}')->delete(),
                                            ],
                                        ],
                                    ]),
                                ]),
                        ]),
                ],
            ],
        ]);

    return success($schema->build());
}
```
