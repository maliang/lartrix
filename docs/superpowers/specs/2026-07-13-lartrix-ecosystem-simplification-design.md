# Lartrix 生态基础设施精简设计

## 目标

对 Lartrix 的模块、项目和模块市场基础设施进行破坏性精简，消除旧协议兼容、多重配置、多份派生配置和重复 Registry 编排，使每类数据只有一个权威来源。

## 全局原则

1. 本次是破坏性升级，不保留 `module_registry`、旧 Trix 平铺字段和旧项目 JSON 存储的兼容读取。
2. 不为了抽象而增加接口或父类；只有具备独立职责和多个真实调用者的流程才提取公共组件。
3. 安全流程不可因精简而弱化：checksum、签名、路径预检、staging、显式目标目录、默认不覆盖、更新备份和失败回滚必须保留。
4. Lartrix 先完成改造；Thinkrix 在 Lartrix 协议稳定后按相同公开协议单独迁移。

## 一、模块市场配置

删除 `lartrix.module_registry`，所有市场查询、发布、下载和签名配置统一到：

```php
'module_market' => [
    'enabled' => env('LARTRIX_MODULE_MARKET_ENABLED', true),
    'url' => env('LARTRIX_MODULE_MARKET_URL', ''),
    'auth_key' => env('TRIX_AUTH_KEY', ''),
    'signature_key' => env('LARTRIX_MODULE_MARKET_SIGNATURE_KEY', ''),
    'timeout' => env('LARTRIX_MODULE_MARKET_TIMEOUT', 30),
    'cache_ttl' => env('LARTRIX_MODULE_MARKET_CACHE_TTL', 3600),
];
```

不再保留 `api_url`、`module_registry.url` 或其他别名。所有代码只读取 `lartrix.module_market.*`。

## 二、模块清单

Lartrix 模块继续使用 Nwidart 约定的根目录 `module.json`。根字段归 Nwidart 管理，Trix 生态协议固定放在 `trix` 节点：

```json
{
  "name": "Member",
  "alias": "member",
  "description": "会员模块",
  "keywords": [],
  "priority": 0,
  "providers": [
    "Modules\\Member\\Providers\\MemberServiceProvider"
  ],
  "files": [],
  "trix": {
    "schema_version": "trix.module.v1",
    "id": "official.member",
    "name": "会员",
    "version": "1.0.0",
    "type": "native",
    "adapter": {
      "language": "php",
      "framework": "laravel",
      "package_type": "nwidart"
    }
  }
}
```

Nwidart 根字段不是旧格式，也不由 Trix Loader 重写；它负责模块发现、Provider 注册、加载顺序和辅助文件加载。删除旧 Trix 平铺格式归一化和 `legacy.*` ID 生成逻辑。

Loader 必须按固定顺序执行：读取 JSON、校验 Nwidart 必需字段、提取 `trix` 节点、校验 Trix Schema、构造 `ModuleManifest`。任何字段错误都直接失败。

`ModuleManifest` 只包装 `trix` 节点，不包装整个 Nwidart JSON。`ModuleManifest::fromArray()` 不再作为可绕过校验的公共入口。生产代码获取到 `ModuleManifest` 即代表 Trix 清单已合法。

`lartrix:module-make` 先使用 Nwidart 生成模块，再通过 JSON 结构化写入补充标准 `trix` 节点，不创建第二个清单文件，也不覆盖 `providers`、`priority` 和 `files`。

## 三、唯一模块标识

只保留：

- 根 `name`：本地 Nwidart 模块名称和目录名称，例如 `Member`。
- `trix.id`：Trix 生态唯一 ID，例如 `official.member`。

同步模块时将 `module.json.trix.id` 明确投影为数据库 `registry_id`。Registry 查询、发布、更新和项目依赖只使用 `registry_id`。缺少生态 ID 时拒绝市场操作，不再从 title、alias、目录名或本地模块名猜测。

数据库 `config` 不再同时保存平铺 Manifest 和 `trix_manifest` 副本。数据库只保存后台展示和生命周期需要的明确投影字段；完整 Trix 协议始终从模块目录的 `module.json.trix` 读取，Nwidart 运行元数据继续由 Nwidart 自己读取根字段。

## 四、Adapter 权威边界

包内 `module.json.trix.adapter` 只声明不可变技术信息：

- language
- language_version
- framework
- framework_version
- package_type
- 入口或安装元数据

`stable`、`experimental`、`planned`、`unsupported` 等发布状态只由模块市场数据库维护。包内不再保存或校验 `adapter.status`。

本地校验只确认当前包确实属于请求的语言、框架、模块 ID 和版本。是否允许下载和安装由市场 API 返回的 Adapter 状态决定一次。

## 五、项目配置

项目运行时唯一配置文件为：

```text
config/trix-project.php
```

结构固定为：

```php
return [
    'schema_version' => 'trix.project.v1',
    'id' => 'official.shop',
    'version' => '1.0.0',
    'project_config' => [],
    'modules' => [
        'official.member' => [
            'version' => '1.0.0',
            'config' => [],
        ],
    ],
    'contract_bindings' => [],
    'setup' => [],
    'installed_at' => null,
];
```

项目安装计划可以作为命令输入或审计数据存在，但不再拆分为 `project-config.json`、`contract-bindings.json`、`setup.json` 和模块配置 JSON。项目安装成功后，安装器原子写入 `config/trix-project.php`。

运行时代码只读取 `config('trix-project.*')`。契约绑定在真正存在消费者前只是该配置中的项目元数据，不宣传为已完成的运行时容器。

## 六、Project Manifest

新增统一的 `ProjectManifest` 和 `ProjectManifestValidator`，负责项目协议版本、必填字段、Adapter、模块依赖、配置和契约绑定结构。

`ProjectMakeCommand`、`ProjectPublishCommand`、后台项目发布和项目安装都调用同一个校验入口，不再各自维护字段集合。

## 七、Registry 公共流程

新增两个有实际职责的公共组件：

### RegistryClient

统一负责：

- 市场 URL
- Auth Key
- timeout
- JSON 请求
- 包下载
- HTTP 状态和错误响应归一化

### RegistryPackagePipeline

统一负责：

```text
下载 → checksum/签名 → ZIP 预检 → staging → Manifest 校验
```

返回稳定结果对象或结构。Controller 和 Command 不再手工重复编排这些步骤，只负责输入、权限、确认、安装和输出。

底层安全组件可以保留为 Pipeline 的内部协作者，但删除入口处重复预检和重复状态判断。

## 八、模块更新

`RegistryStagedManifestVerifier` 成功后返回已解析的合法 `ModuleManifest`。`RegistryModuleUpdatePlanner` 直接接收当前和目标 Manifest，比较 ID、版本和升级/降级规则。

删除 Executor 将包内 Manifest 重新包装成伪 Registry API 数据的过程，也不再次解析 Adapter 发布状态。

## 九、控制器职责

拆分当前 `ModuleController`：

- `ModuleController`：本地模块列表、启停、安装、卸载和 Logo。
- `ModuleMarketController`：市场模块和项目查询、下载与安装入口。
- `ModulePublishController`：本地模块和项目发布。
- `ModuleManagementSchema`：已安装模块页面 Schema。
- `ModuleMarketSchema`：市场弹窗、卡片、详情和分页 Schema。

路由与控制器映射相应调整。业务服务负责流程，控制器不再直接实现 ZIP、HTTP 和版本解析细节。

## 十、BaseService

删除空的 `BaseService`，现有服务直接定义。当前没有稳定、通用且值得继承的事务、日志或错误处理职责。

未来只有在至少两个服务出现相同且不可通过组合解决的公共行为时，才重新引入基类。优先使用独立协作者和 Laravel 原生能力。

## 十一、启用状态

Nwidart Activator 是模块启用状态的唯一权威来源。数据库 `enabled` 只作为展示投影，不独立决定运行状态。

普通模块列表请求不得执行旧名称修复或隐式启用。旧状态键迁移逻辑从请求流程删除；如需要处理开发期遗留状态，使用一次性显式命令或手工迁移。

## 十二、删除项

本次直接删除：

- `lartrix.module_registry` 配置。
- `module_market.api_url` 旧字段。
- 旧 Trix 平铺字段和原生 module.json 自动归一化。
- `legacy.*` 自动 ID。
- `config.trix_manifest` Manifest 副本。
- Adapter 包内发布状态。
- 多份项目派生 JSON 运行时存储。
- 无消费者的项目派生 JSON 读取 API。
- 空 `BaseService`。
- README 中的 `route_prefix` 旧示例。
- 请求阶段模块状态别名自动修复。

## 十三、错误处理

- Manifest 不合法：明确列出字段错误并停止发现、安装或发布。
- 项目配置写入失败：安装命令失败，不报告安装完成。
- Registry HTTP 错误：由 `RegistryClient` 统一归一化。
- 包安全检查失败：Pipeline 立即停止，不复制到模块目录。
- 生态 ID 缺失：本地模块仍可运行，但禁止市场查询、发布和项目依赖解析。
- 已存在模块目录：不覆盖，继续验证本地模块生命周期状态。

## 十四、测试与验收

必须覆盖：

1. 只有 Nwidart 根字段、没有 `trix` 节点的模块仍可被 Nwidart 运行，但不能参与 Trix 市场操作。
2. `trix` 节点任一关键字段非法时 Loader 失败。
3. Nwidart 的 `name/providers/priority/files` 在生成和更新 Trix 元数据后保持不变。
4. Registry ID 不再从本地名称猜测。
5. 全部代码只读取 `module_market`。
6. 项目安装只写 `config/trix-project.php`，不生成派生 JSON。
7. PHP 配置能够被 Laravel 正常加载，并保留模块配置、契约绑定和 setup。
8. CLI 与 HTTP 发布共用 Project Manifest 校验。
9. 三个安装入口共用 Registry Pipeline。
10. Adapter 发布状态只来自市场响应。
11. 模块列表请求不隐式启用模块。
12. 模块更新不再构造伪 Registry payload。
13. 原有 checksum、签名、路径预检、staging、备份和回滚测试继续通过。

## 十五、非目标

- 本阶段不实现新的 ContractManager 或 Laravel 动态服务绑定。
- 本阶段不修改 Thinkrix。
- 本阶段不改变 Trixmore 市场数据库结构，除非 API 契约测试证明现有响应无法满足单一 Adapter 状态来源。
- 本阶段不删除 Schema UI、安全校验或模块生命周期能力。
