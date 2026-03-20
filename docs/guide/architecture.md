# 架构概览

Lartrix 采用分层架构设计，将业务逻辑、数据访问和界面表现清晰分离。

## 系统架构图

```
┌─────────────────────────────────────────────────────────────┐
│                      Frontend (Trix)                       │
│                   Vue 3 + NaiveUI + vschema-ui             │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ HTTP / JSON Schema
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Lartrix Package                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │  │   Schema     │  │   Models     │      │
│  │              │  │  Components  │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Actions    │  │   Services   │  │ Middleware   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ Eloquent
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Framework                        │
│        Routing │ Auth │ Validation │ Cache │ Queue          │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database / Storage                       │
└─────────────────────────────────────────────────────────────┘
```

## 核心组件

### 1. Schema 系统

Schema 系统是 Lartrix 的核心，它将 PHP 代码转换为前端可渲染的 JSON Schema。

```php
// PHP 代码
Button::make('点击我')
    ->type('primary')
    ->on('click', SetAction::make('visible', true));

// 转换为 JSON
{
    "com": "NButton",
    "props": {
        "type": "primary"
    },
    "on": {
        "click": [{"action": "set", "name": "visible", "value": true}]
    }
}
```

### 2. CrudController

提供完整的 CRUD 基础实现，你只需配置模型和界面：

```php
class PostController extends CrudController
{
    protected function getModelClass(): string
    {
        return Post::class;
    }
    
    // 自动生成 list/create/update/delete 接口
}
```

### 3. Action 系统

Action 是前端交互的抽象，支持链式调用：

- **SetAction**: 设置状态
- **CallAction**: 调用方法
- **FetchAction**: HTTP 请求
- **IfAction**: 条件判断

### 4. 权限系统

基于 Spatie Laravel Permission 的 RBAC 实现：

- 多 Guard 支持
- 动态权限分配
- 菜单级权限控制

## 数据流

```
1. 用户操作 → 前端发送请求
2. Controller 接收请求
3. 根据 action_type 分发处理
4. 返回 JSON Schema
5. 前端渲染界面
```

## 模块系统

基于 nwidart/laravel-modules，支持：

- 独立路由
- 独立迁移
- 独立配置
- 热插拔

## 扩展点

- 自定义 Schema 组件
- 自定义 Actions
- 自定义验证规则
- 自定义导出格式
