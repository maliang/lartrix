# 数据字典

数据字典用于管理应用中常用的枚举值，如状态、类型等。

## 使用场景

- 用户状态：启用/禁用
- 订单状态：待付款/已付款/已发货
- 文章分类：技术/生活/随笔

## 管理界面

登录后台，进入"系统管理" -> "数据字典"。

## 在代码中使用

### 获取字典选项

```php
use Lartrix\\Services\\DataDictService;

$options = DataDictService::getOptions('status');
// 返回: [['label' => '启用', 'value' => 1], ...]
```

### 在 Schema 中使用

```php
Select::make()
    ->options('{{ $dict.status }}');
```

### 在模型中使用

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
