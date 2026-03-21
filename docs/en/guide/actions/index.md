# Action Overview

Actions are abstractions in Lartrix that describe user interaction behaviors, allowing you to define frontend logic with PHP code.

## What is an Action?

An Action is a configuration object describing "what should happen when a user does something":

```php
// When user clicks button
Button::make('Save')
    ->on('click', [
        // Set loading state
        SetAction::make('saving', true),
        // Send save request
        FetchAction::make('/api/posts')
            ->post()
            ->body(['title' => '{{ form.title }}'])
            ->then([
                SetAction::make('saving', false),
                CallAction::make('$message.success', ['Saved']),
                SetAction::make('visible', false),
            ]),
    ]);
```

## Action Types

| Action | Description |
|--------|-------------|
| `SetAction` | Set reactive data |
| `CallAction` | Call built-in or custom methods |
| `FetchAction` | Send HTTP requests |
| `IfAction` | Conditional logic |
| `ScriptAction` | Execute scripts |
| `EmitAction` | Trigger custom events |
| `CopyAction` | Copy to clipboard |
| `WebSocketAction` | WebSocket operations |

## Method Chaining

Most Actions support method chaining:

```php
FetchAction::make('/api/users/{id}')
    ->put()                           // HTTP method
    ->body(['status' => true])       // Request body
    ->headers(['X-Custom' => 'value']) // Headers
    ->then([...])                     // Success callback
    ->catch([...]);                   // Error callback
```

## Detailed Documentation

- [SetAction](/en/guide/actions/set) - Set state
- [CallAction](/en/guide/actions/call) - Call methods
- [FetchAction](/en/guide/actions/fetch) - HTTP requests
- [IfAction](/en/guide/actions/if) - Conditional logic
- [Other Actions](/en/guide/actions/others) - Script, Emit, Copy, etc.
