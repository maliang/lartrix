# 介绍

Lartrix 是一个功能强大的 Laravel 后台管理包，它通过 **PHP Schema Builder** 让你用 PHP 代码就能构建出现代化的管理后台界面。

## 核心特性

### PHP Schema Builder

使用 PHP 代码描述界面，自动生成兼容 vschema-ui 的 JSON Schema。这意味着：

- 无需手写 Vue/React 代码
- 前后端使用同一套数据模型
- 界面改动只需修改 PHP 代码
- 类型安全，IDE 友好

```php
use Lartrix\Schema\Components\NaiveUI\{Button, Input, SwitchC};
use Lartrix\Schema\Components\Business\CrudPage;
use Lartrix\Schema\Actions\{SetAction, FetchAction, CallAction};

protected function listUi(): array
{
    $schema = CrudPage::make('用户管理')
        ->apiPrefix('/users')
        ->columns([
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'name', 'title' => '用户名'],
            ['key' => 'status', 'title' => '状态', 'slot' => [
                SwitchC::make()
                    ->props(['value' => '{{ slotData.row.status }}'])
                    ->on('update:value', FetchAction::make('/users/{{ slotData.row.id }}')
                        ->put()
                        ->body(['action_type' => 'status', 'status' => '{{ $event }}'])
                        ->then([CallAction::make('$message.success', ['更新成功'])])
                    ),
            ]],
        ]);

    return success($schema->build());
}
```

## 技术栈

| 组件 | 技术 |
|------|------|
| 后端框架 | Laravel 10/11/12 |
| PHP 版本 | 8.1+ |
| 认证 | Laravel Sanctum |
| 权限 | Spatie Laravel Permission |
| 模块系统 | nwidart/laravel-modules |
| 前端框架 | Vue 3 + NaiveUI |

## 下一步

- [安装 Lartrix](/guide/installation) - 了解如何安装和配置
- [快速开始](/guide/quickstart) - 5 分钟上手教程
