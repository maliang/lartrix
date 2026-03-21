# Custom Components

Extend Lartrix with custom components.

## Create Custom Component

```php
<?php

namespace App\Schema\Components;

use Lartrix\Schema\Components\Component;

class MyComponent extends Component
{
    protected string $component = 'MyComponent';

    public static function make(): static
    {
        return new static();
    }

    public function customMethod($value): static
    {
        $this->props['customProp'] = $value;
        return $this;
    }
}
```

## Register Component

Register in `AppServiceProvider`:

```php
use Lartrix\Schema\ComponentRegistry;
use App\Schema\Components\MyComponent;

public function boot()
{
    ComponentRegistry::register('my', MyComponent::class);
}
```

## Use Custom Component

```php
use App\Schema\Components\MyComponent;

$component = MyComponent::make()
    ->customMethod('value')
    ->props(['size' => 'large']);
```

## Register Frontend Component

In your Vue app:

```javascript
import MyComponent from './components/MyComponent.vue'

app.component('MyComponent', MyComponent)
```

## Component Output

```php
$component->toArray();
// Output:
// {
//   "com": "MyComponent",
//   "props": {
//     "customProp": "value",
//     "size": "large"
//   }
// }
```

## Best Practices

1. Extend `Component` base class
2. Define `$component` property
3. Implement `make()` static method
4. Use method chaining
5. Register in frontend
