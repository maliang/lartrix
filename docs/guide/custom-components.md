# 自定义组件

当内置组件不能满足需求时，可以创建自定义组件。

## 创建组件类

```php
<?php

namespace App\\Schema\\Components;

use Lartrix\\Schema\\Components\\Component;

class MyComponent extends Component
{
    protected string $com = 'MyComponent';

    public static function make(string $text = null): static
    {
        return new static(['text' => $text]);
    }

    public function color(string $color): static
    {
        return $this->props(['color' => $color]);
    }
}
```

## 注册组件

在 `AppServiceProvider` 中注册：

```php
use Lartrix\\Schema\\ComponentRegistry;
use App\\Schema\\Components\\MyComponent;

public function boot()
{
    ComponentRegistry::register('my', MyComponent::class);
}
```

## 使用组件

```php
use App\\Schema\\Components\\MyComponent;

MyComponent::make('Hello')
    $table->color('red');
```

## 创建对应的前端组件

在 Trix 前端项目中创建 `MyComponent.vue`：

```vue
<template>
    <div :style="{ color: props.color }">
        {{ props.text }}
    </div>
</template>

<script setup>
const props = defineProps({
    text: String,
    color: { type: String, default: 'black' }
});
</script>
```

注册到 vschema-ui 组件库中。

## 组件输出

```php
$component->toArray();
// 输出：
// {
//   "com": "MyComponent",
//   "props": {
//     "customProp": "value",
//     "size": "large"
//   }
// }
```

## 最佳实践

1. 继承 `Component` 基类
2. 定义 `$com` 属性
3. 实现 `make()` 静态方法
4. 使用链式调用
5. 在前端注册组件
