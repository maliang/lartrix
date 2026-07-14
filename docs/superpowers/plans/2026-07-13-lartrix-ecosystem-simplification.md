# Lartrix 生态基础设施精简实施计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**目标：** 以破坏性升级方式消除 Lartrix 模块生态中的多重配置、旧 Manifest 兼容、项目派生 JSON 和重复 Registry 编排，同时保留全部安全与生命周期能力。

**架构：** Nwidart 继续拥有 `module.json` 根字段，Trix 严格协议统一位于 `module.json.trix`。市场访问统一通过 `module_market`、`RegistryClient` 和 `RegistryPackagePipeline`；项目运行时配置只使用 `config/trix-project.php`。

**技术栈：** PHP 8.2、Laravel、Nwidart Laravel Modules、Laravel HTTP Client、PHPUnit、JSON、PHP 配置文件。

## 全局约束

- 不兼容 `module_registry`、旧 Trix 平铺字段和旧项目派生 JSON。
- 不修改 Thinkrix。
- 保留 checksum、签名、ZIP 路径预检、staging、默认不覆盖、更新备份与回滚。
- 所有新增和修改类、方法使用中文注释。
- 不直接修改 `resources/admin` 构建产物。

---

### 任务一：严格的组合 Manifest

**文件：**
- 修改：`src/Modules/Manifest/ModuleManifestLoader.php`
- 修改：`src/Modules/Manifest/ModuleManifestValidator.php`
- 修改：`src/Modules/Manifest/ModuleManifest.php`
- 修改：`src/Services/ModuleService.php`
- 修改：`src/Commands/ModuleMakeCommand.php`
- 修改：`stubs/module/module.json.stub`
- 测试：`tests/Unit/Modules/Manifest/*`

**接口：**
- 产出：`ModuleManifestLoader::loadFromPath(string): ?ModuleManifest`
- 约束：返回对象只包含 `module.json.trix`；返回成功即已通过完整校验。

- [x] 编写失败测试：没有 `trix` 节点返回 `null`，旧平铺 Trix 字段不被归一化。
- [x] 编写失败测试：Nwidart 必需字段或 `trix` 字段不合法时抛出明确异常。
- [x] 编写失败测试：模块生成后保留 `providers/priority/files` 并写入 `trix` 节点。
- [x] 实现严格 Loader，删除 legacy 归一化及公开绕过校验入口。
- [x] 移除包内 `adapter.status` 校验，只校验技术 Adapter 身份和版本字段。
- [x] 将数据库投影统一为 `trix.id -> registry_id`，不再保存平铺 Manifest 与 `trix_manifest`。
- [x] 运行 Manifest 与 ModuleService 聚焦测试（2026-07-13 已执行；ModuleService 数据库用例受本机缺少 `pdo_sqlite` 阻塞）。

### 任务二：统一模块市场配置与客户端

**文件：**
- 修改：`config/lartrix.php`
- 新建：`src/Modules/Registry/RegistryClient.php`
- 修改：`src/Controllers/ModuleController.php`
- 修改：`src/Commands/ModuleInstallCommand.php`
- 修改：`src/Commands/ProjectInstallCommand.php`
- 修改：`src/Commands/ProjectPublishCommand.php`
- 测试：`tests/Unit/Modules/Registry/RegistryClientTest.php`

**接口：**
- 产出：`RegistryClient::getJson(string, array): array`
- 产出：`RegistryClient::download(string): ?string`
- 产出：`RegistryClient::request(): PendingRequest`

- [x] 编写配置测试，断言只有 `module_market` 且包含 `enabled/url/auth_key/signature_key/timeout/cache_ttl`。
- [x] 编写 RegistryClient HTTP 成功、认证、超时和错误归一化测试。
- [x] 删除 `module_registry` 与 `module_market.api_url`。
- [x] 实现 RegistryClient 并替换所有 URL/Auth Key/PendingRequest 重复代码。
- [x] 全仓搜索并确保生产代码不存在 `module_registry`。
- [x] 运行 Registry 配置与客户端测试。

### 任务三：统一 Registry 包准备管线

**文件：**
- 新建：`src/Modules/Registry/RegistryPackagePipeline.php`
- 修改：`src/Modules/Registry/RegistryPackageDownloader.php`
- 修改：`src/Modules/Registry/RegistryPackageStager.php`
- 修改：`src/Controllers/ModuleController.php`
- 修改：`src/Commands/ModuleInstallCommand.php`
- 修改：`src/Commands/ProjectInstallCommand.php`
- 测试：`tests/Unit/Modules/Registry/RegistryPackagePipelineTest.php`

**接口：**
- 产出：`RegistryPackagePipeline::prepare(array $adapter, string $id, string $version): array`
- 返回：`ok/reason/message/path/manifest/security`。

- [x] 编写下载、checksum、签名、预检、解压和 Manifest 校验各失败点测试。
- [x] 实现 Pipeline，内部只执行一次预检并返回统一错误结构。
- [x] 三个入口改为调用 Pipeline，不再手工编排 Downloader/Stager/Verifier。
- [x] 保留显式复制与更新替换作为 Pipeline 之后的独立动作。
- [x] 运行全部 Registry 单元测试。

### 任务四：项目 Manifest 与唯一运行时配置

**文件：**
- 新建：`src/Modules/Project/ProjectManifest.php`
- 新建：`src/Modules/Project/ProjectManifestValidator.php`
- 重构：`src/Modules/Project/ProjectInstallPlanStore.php`
- 修改：`src/Commands/ProjectMakeCommand.php`
- 修改：`src/Commands/ProjectPublishCommand.php`
- 修改：`src/Commands/ProjectInstallCommand.php`
- 修改：`src/Controllers/ModuleController.php`
- 测试：`tests/Unit/Modules/Project/*`

**接口：**
- 产出：`ProjectManifest::load(string): self`
- 产出：`ProjectInstallPlanStore::apply(array): string`
- 写入：`config/trix-project.php`

- [x] 编写统一项目协议校验测试，覆盖 CLI 与 HTTP 相同规则。
- [x] 编写项目配置写入测试，要求 PHP 文件可 require 且结构完整。
- [x] 编写“不生成派生 JSON”测试。
- [x] 实现 ProjectManifest 与 Validator。
- [x] 将 Store 改为原子写入 `config/trix-project.php`，删除派生 JSON 读取 API。
- [x] 修改项目命令和后台发布入口共用统一 Manifest。
- [x] 运行项目模块测试。

### 任务五：简化更新和启用状态

**文件：**
- 修改：`src/Modules/Registry/RegistryStagedManifestVerifier.php`
- 修改：`src/Modules/Registry/RegistryModuleUpdatePlanner.php`
- 修改：`src/Modules/Registry/RegistryModuleUpdateExecutor.php`
- 修改：`src/Services/ModuleService.php`
- 测试：`tests/Unit/Modules/Registry/RegistryModuleUpdate*Test.php`
- 测试：新增 ModuleService 状态聚焦测试。

- [x] 编写 Planner 直接接收当前/目标 Manifest 的测试。
- [x] 删除伪 Registry payload 和重复 Manifest 读取。
- [x] 以 Nwidart Activator 为启用状态权威，数据库仅投影。
- [x] 删除请求阶段旧状态别名推断和隐式启用。
- [x] 确保安装、卸载、启用、禁用后显式同步数据库投影。
- [x] 运行更新和模块生命周期测试（2026-07-13 已执行；数据库用例受本机缺少 `pdo_sqlite` 阻塞）。

### 任务六：拆分模块控制器与 Schema

**文件：**
- 修改：`src/Controllers/ModuleController.php`
- 新建：`src/Controllers/ModuleMarketController.php`
- 新建：`src/Controllers/ModulePublishController.php`
- 新建：`src/Schema/Pages/ModuleManagementSchema.php`
- 新建：`src/Schema/Pages/ModuleMarketSchema.php`
- 修改：`routes/api.php`
- 修改：`config/lartrix.php`
- 测试：`tests/Feature/ModuleControllerTest.php`

- [x] 编写路由和控制器职责测试。
- [x] 将市场查询与安装入口迁移到 ModuleMarketController，并由 `ModuleMarketService` 承接业务。
- [x] 将模块/项目发布、ZIP 安全打包迁移到 ModulePublishController，并由 `ModulePublishService` 承接业务。
- [x] 将已安装模块页与市场弹窗 Schema 迁移到两个独立 Builder。
- [x] 保持 API 路径和权限名不变。
- [x] 运行模块 Feature 测试和 Schema 回归。

### 任务七：删除冗余与更新文档

**文件：**
- 删除：`src/Services/BaseService.php`
- 修改：所有继承 BaseService 的服务。
- 修改：`README.md`
- 修改：`tests/core_regression.php`
- 修改：相关 Stub、示例和文档。

- [x] 删除空 BaseService 和所有无价值继承。
- [x] 修正 README 的 `api_prefix`、`module_market` 与组合 Manifest 示例。
- [x] 更新示例模块为 Nwidart 根字段加 `trix` 节点。
- [x] 全仓搜索旧配置、旧 Manifest、派生 JSON 和旧状态别名。
- [x] 更新 core regression 断言到新架构。

> 2026-07-13 最终复核：任务六已完成真实迁移。`ModuleController` 仅保留本地模块列表、生命周期和 Logo；市场与发布控制器分别依赖独立应用服务，两个 Schema Builder 也已物理拆分。

### 任务八：完整验证与独立审查

**文件：**
- 测试：`tests/`

- [x] 对所有修改 PHP 文件运行 `php -l`。
- [x] 运行 Manifest、Project、Registry、Schema 聚焦测试。
- [x] 运行 `php tests/core_regression.php`。
- [x] 运行完整 PHPUnit；确认 90 个错误均由本机缺少 `pdo_sqlite` 引起，业务断言无失败，并补跑全部不依赖数据库的测试。
- [x] 运行 `git diff --check`。
- [x] 独立审查单一事实来源、权限、安全和兼容删除是否完整。
- [x] 修复审查问题并重新执行相关验证。
