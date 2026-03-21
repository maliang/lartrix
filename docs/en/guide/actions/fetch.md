# FetchAction

FetchAction is used to send HTTP requests.

## Basic Usage

```php
use Lartrix\Schema\Actions\FetchAction;

FetchAction::make('/api/users')->get();
```

## HTTP Methods

```php
// GET
FetchAction::make('/api/users')->get();

// POST
FetchAction::make('/api/users')->post();

// PUT
FetchAction::make('/api/users/1')->put();

// DELETE
FetchAction::make('/api/users/1')->delete();

// PATCH
FetchAction::make('/api/users/1')->patch();
```

## Request Body

```php
FetchAction::make('/api/users')
    ->post()
    ->body([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
```

## Dynamic Values

```php
// Use expressions
FetchAction::make('/api/users/{{ userId }}')
    ->put()
    ->body([
        'status' => '{{ $event }}',
    ]);
```

## Headers

```php
FetchAction::make('/api/users')
    ->headers([
        'X-Custom-Header' => 'value',
    ]);
```

## Success Callback

```php
FetchAction::make('/api/users')
    ->post()
    ->then([
        CallAction::make('$message.success', ['Created']),
        CallAction::make('loadData'),
        SetAction::make('visible', false),
    ]);
```

## Error Callback

```php
FetchAction::make('/api/users')
    ->post()
    ->catch([
        CallAction::make('$message.error', ['Failed']),
    ]);
```

## Complete Example

```php
Button::make('Save')
    ->on('click', [
        SetAction::make('saving', true),
        FetchAction::make('/api/posts')
            ->post()
            ->body(['data' => '{{ formData }}'])
            ->then([
                SetAction::make('saving', false),
                CallAction::make('$message.success', ['Saved']),
                SetAction::make('visible', false),
                CallAction::make('loadData'),
            ])
            ->catch([
                SetAction::make('saving', false),
                CallAction::make('$message.error', ['Save failed']),
            ]),
    ]);
```

## Response Handling

```php
FetchAction::make('/api/users')
    ->then([
        // Response data available as {{ $response }}
        SetAction::make('userData', '{{ $response.data }}'),
    ]);
```
