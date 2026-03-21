# Data Dictionary

Data dictionary is used to manage common enumeration values in the application, such as statuses and types.

## Usage Scenarios

- User status: enabled / disabled
- Order status: pending payment / paid / shipped
- Article categories: technology / lifestyle / notes

## Admin Interface

Log in to the admin panel and go to "System" -> "Data Dictionary".

## Usage in Code

### Get Dictionary Options

```php
use Lartrix\Services\DataDictService;

$options = DataDictService::getOptions('status');
// Returns: [['label' => 'Enabled', 'value' => 1], ...]
```

### Use in Schema

```php
Select::make()
    ->options('{{ $dict.status }}');
```

### Use in Models

```php
class Order extends Model
{
    public function getStatusTextAttribute()
    {
        return DataDictService::getLabel('order_status', $this->status);
    }
}
```

## API

- GET /api/admin/dicts/options?code=status
- GET /api/admin/dict/groups
- GET /api/admin/dict/items?group_id=1
