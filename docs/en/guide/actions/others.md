# Other Actions

## ScriptAction

Execute custom JavaScript code.

```php
use Lartrix\Schema\Actions\ScriptAction;

ScriptAction::make('console.log("Hello")');
```

## EmitAction

Trigger custom events.

```php
use Lartrix\Schema\Actions\EmitAction;

EmitAction::make('refresh-data', ['id' => 1]);
```

Listen to events:

```php
Component::make()
    ->on('refresh-data', [
        CallAction::make('loadData'),
    ]);
```

## CopyAction

Copy text to clipboard.

```php
use Lartrix\Schema\Actions\CopyAction;

Button::make('Copy')
    ->on('click', [
        CopyAction::make('{{ row.code }}'),
        CallAction::make('$message.success', ['Copied']),
    ]);
```

## WebSocketAction

WebSocket operations.

```php
use Lartrix\Schema\Actions\WebSocketAction;

// Connect
WebSocketAction::make('connect', 'ws://localhost:8080');

// Send message
WebSocketAction::make('send', ['type' => 'ping']);

// Disconnect
WebSocketAction::make('disconnect');
```
