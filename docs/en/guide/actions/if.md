# IfAction

IfAction is used for conditional logic.

## Basic Usage

```php
use Lartrix\Schema\Actions\IfAction;

IfAction::make('{{ status === 1 }}')
    ->then([
        CallAction::make('$message.success', ['Active']),
    ])
    ->else([
        CallAction::make('$message.warning'
