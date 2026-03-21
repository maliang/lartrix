# Actions

## ActionInterface

Action interface.

```php
Lartrix\Schema\Actions\ActionInterface
```

## SetAction

Set state value.

```php
use Lartrix\Schema\Actions\SetAction;

SetAction::make('name', 'value')->toArray();

// Output
// ['action' => 'set', 'name' => 'name', 'value' => 'value']
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| $name | string | Variable name, supports dot notation |
| $value | mixed | Value |

## CallAction

Call a method.

```php
use Lartrix\Schema\Actions\CallAction;

CallAction::make('$message.success', ['param1', 'param2'])->toArray();
```

### Built-in Methods

| Method | Description |
|--------|-------------|
| `$message.success` | Success message |
| `$message.error` | Error message |
| `$message.warning` | Warning message |
| `$message.info` | Info message |
| `$dialog.success` | Success dialog |
| `$dialog.error` | Error dialog |
| `$dialog.warning` | Warning dialog |
| `$loadingBar.start` | Start loading bar |
| `$loadingBar.finish` | Finish loading bar |
| `$loadingBar.error` | Loading bar error |
| `$nav.push` | Navigate to page |
| `$nav.replace` | Replace current page |
| `$nav.back` | Go back |
| `$tab.close` | Close tab |
| `$tab.open` | Open tab |
| `$window.open` | Open new window |
| `$download` | Download file |

## FetchAction

HTTP request action.

```php
use Lartrix\Schema\Actions\FetchAction;

FetchAction::make('/api/users')
    ->get()
    ->params(['page' => 1])
    ->then([...])
    ->catch([...]);
```

### HTTP Methods

| Method | Description |
|--------|-------------|
| `get()` | GET request |
| `post()` | POST request |
| `put()` | PUT request |
| `patch()` | PATCH request |
| `delete()` | DELETE request |

### Chaining Methods

| Method | Description |
|--------|-------------|
| `body($data)` | Request body |
| `params($params)` | URL parameters |
| `headers($headers)` | Request headers |
| `then($actions)` | Success callback |
| `catch($actions)` | Failure callback |
| `finally($actions)` | Final callback |

## IfAction

Conditional action.

```php
use Lartrix\Schema\Actions\IfAction;

IfAction::make('{{ condition }}')
    ->true([...])
    ->false([...]);
```

## ScriptAction

Execute a script.

```php
use Lartrix\Schema\Actions\ScriptAction;

ScriptAction::make('console.log("debug")');
```

## EmitAction

Emit an event.

```php
use Lartrix\Schema\Actions\EmitAction;

EmitAction::make('event-name', ['param']);
```

## CopyAction

Copy to clipboard.

```php
use Lartrix\Schema\Actions\CopyAction;

CopyAction::make('text to copy');
```

## WebSocketAction

WebSocket operation.

```php
use Lartrix\Schema\Actions\WebSocketAction;

WebSocketAction::make('channel')
    ->action('send')
    ->payload(['data' => 'value']);
```
