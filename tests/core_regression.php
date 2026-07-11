<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

function check(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

function source(string $path): string
{
    global $root;
    return file_get_contents($root . DIRECTORY_SEPARATOR . $path);
}

$settingModel = source('src/Models/Setting.php');
check(
    str_contains($settingModel, 'public static function fetchThemeConfig')
        && str_contains($settingModel, "static::getGroup('login')")
        && str_contains($settingModel, "array_key_exists('appTitle', \$loginSettings)")
        && str_contains($settingModel, "array_key_exists('app_title', \$loginSettings)"),
    'Setting must expose fetchThemeConfig() that merges login/site settings into theme config.'
);

$settingController = source('src/Controllers/SettingController.php');
check(
    str_contains($settingController, 'syncThemeSettings')
        && str_contains($settingController, "'login.appTitle' => 'appTitle'")
        && str_contains($settingController, "Setting::set('theme'"),
    'SettingController must sync app title/logo/copyright writes into the theme setting.'
);
check(
    !str_contains($settingController, 'exists:admin_settings,key'),
    'SettingController must allow missing login.* setting keys so site settings can be created.'
);
$oneImgUp = source('src/Schema/Components/Business/OneImgUp.php');
check(
    str_contains($settingController, "OneImgUp::make('formData.logo')")
        && str_contains($settingController, '->action($uploadAction)')
        && !str_contains($settingController, "Input::make()->model('formData.logo')"),
    'SettingController logo field must use the OneImgUp single-image upload component bound to formData.logo.'
);
check(
    str_contains($settingController, "'call' => '\$methods.\$theme.updateSite'")
        && str_contains($settingController, "'args' => ['{{ formData.appTitle }}', '{{ formData.logo }}']"),
    'SettingController must update the frontend theme title and logo immediately after saving site settings.'
);
check(
    str_contains($oneImgUp, 'JSON.parse($event.event.target.response)')
        && str_contains($oneImgUp, '->showFileList(false)')
        && str_contains($oneImgUp, 'previewDisabled')
        && str_contains($oneImgUp, "'mouseenter'")
        && str_contains($oneImgUp, "'click.stop'")
        && str_contains($oneImgUp, "->listType('image-card')")
        && str_contains($oneImgUp, "->on('remove'")
        && !str_contains($oneImgUp, "\$event.file.response"),
    'OneImgUp: replace mode uses click-to-replace + hover delete; preview mode uses native image-card overlay (eye/trash) with delete-to-reupload; URL read from XHR response.'
);

$systemController = source('src/Controllers/SystemController.php');
check(
    str_contains($systemController, 'fetchThemeConfig($this->getDefaultThemeConfig())'),
    'SystemController must read merged theme config so site settings update entry, login and layout titles.'
);
check(
    str_contains($systemController, "'realtime' => \$this->getRealtimeConfig()")
        && str_contains($systemController, "'behaviors' => config('lartrix.realtime.behaviors', [])"),
    'SystemController must expose realtime only through the entry config payload.'
);
check(
    !str_contains($systemController, '->enablePolling(')
        && !str_contains($systemController, '->pollingInterval(')
        && !str_contains($systemController, '->pollingApi(')
        && !str_contains($systemController, '->enableWs(')
        && !str_contains($systemController, '->wsUrl('),
    'SystemController must not bind polling/ws realtime props to HeaderNotification.'
);
check(
    !str_contains($systemController, "__t('system.forgot_password')")
        && !str_contains($systemController, '$this->buildResetPasswordForm(),'),
    'SystemController login page must not expose the forgot-password/reset-password entry.'
);
check(
    str_contains($systemController, 'protected function buildLogoHeader(string $appTitle, string $appSubtitle, string $logo): Flex')
        && !str_contains($systemController, "->props(['src' => \$theme['logo']"),
    'SystemController login logo header must not read an out-of-scope $theme variable.'
);

$headerNotification = source('src/Schema/Components/Common/HeaderNotification.php');
check(
    !str_contains($headerNotification, 'function enablePolling')
        && !str_contains($headerNotification, 'function pollingInterval')
        && !str_contains($headerNotification, 'function pollingApi')
        && !str_contains($headerNotification, 'function sinceId')
        && !str_contains($headerNotification, 'function enableWs')
        && !str_contains($headerNotification, 'function wsUrl'),
    'HeaderNotification schema component must only expose display/list props, not realtime polling/ws props.'
);

$authController = source('src/Controllers/AuthController.php');
check(
    str_contains($authController, 'Setting::fetchThemeConfig'),
    'AuthController config must read merged theme config.'
);

$moduleController = source('src/Controllers/ModuleController.php');
$moduleService = source('src/Services/ModuleService.php');
check(
    str_contains($moduleController, "->props(['src' => '{{ slotData.row.logo }}', 'size' => 32, 'objectFit' => 'contain'])")
        && !str_contains($moduleController, 'routePrefix + "/modules/" + slotData.row.name + "/logo"'),
    'ModuleController installed module list must use the stored static logo URL directly instead of routing through /api/admin/modules/{name}/logo.'
);
check(
    str_contains($moduleService, 'protected function moduleLogoUrl')
        && str_contains($moduleService, "config('lartrix.api_prefix', 'api/admin')")
        && str_contains($moduleService, "rawurlencode(\$name) . '/logo'"),
    'ModuleService must convert module-local relative logo paths into public module logo URLs during sync.'
);
check(
    str_contains($moduleService, 'resolveModuleEnabledState')
        && str_contains($moduleService, 'hasEnabledLegacyModuleStatus')
        && str_contains($moduleService, "config('modules.activators.file.statuses-file'")
        && str_contains($moduleService, "preg_replace('/(?<!^)[A-Z]/', ' \$0', \$alias)"),
    'ModuleService must migrate legacy nwidart status keys such as "Module Market" to the current standard module key during sync.'
);
check(
    str_contains($moduleController, 'public function publishLocal')
        && str_contains($moduleController, 'public function installMarketModule')
        && str_contains($moduleController, 'public function installMarketProject')
        && str_contains($moduleController, 'protected function saveProjectInstallPlan')
        && str_contains($moduleController, 'protected function registryPublisher')
        && str_contains($moduleController, 'protected function registryPublisherOrNull')
        && str_contains($moduleController, 'protected function validateLocalAuthor')
        && str_contains($moduleController, 'protected function validatePublishVersion')
        && str_contains($moduleController, 'registryLatestModuleVersion')
        && str_contains($moduleController, "'can_publish'")
        && str_contains($moduleController, "->if('slotData.row.can_publish')")
        && str_contains($moduleController, "/registry/auth/me")
        && str_contains($moduleController, "'handlePublishModule'")
        && str_contains($moduleController, "'handleInstallMarketModule'")
        && str_contains($moduleController, "'handleInstallMarketProject'"),
    'ModuleController installed module UI must expose upload and market module/project install actions while checking Auth Key ownership.'
);
$projectMakeCommand = source('src/Commands/ProjectMakeCommand.php');
$projectInstallCommand = source('src/Commands/ProjectInstallCommand.php');
$projectPublishCommand = source('src/Commands/ProjectPublishCommand.php');
$projectInstallPlanStore = source('src/Modules/Project/ProjectInstallPlanStore.php');
$serviceProvider = source('src/LartrixServiceProvider.php');
check(
    str_contains($projectMakeCommand, "protected \$signature = 'lartrix:project-make")
        && str_contains($projectMakeCommand, "base_path('trix-project.json')")
        && str_contains($projectMakeCommand, "'schema_version' => 'trix.project.v1'")
        && str_contains($projectMakeCommand, "'adapter' => [")
        && str_contains($projectMakeCommand, 'ModuleFacade::all()'),
    'ProjectMakeCommand must create the root trix-project.json manifest and sync installed modules.'
);
check(
    str_contains($projectPublishCommand, "protected \$signature = 'lartrix:project-publish")
        && str_contains($projectPublishCommand, '/registry/auth/me')
        && str_contains($projectPublishCommand, '/registry/publish/projects')
        && str_contains($projectPublishCommand, 'validateAuthor')
        && str_contains($projectPublishCommand, 'validateVersion'),
    'ProjectPublishCommand must validate Auth Key author ownership/version before publishing trix-project.json.'
);
check(
    str_contains($serviceProvider, 'Commands\\ProjectMakeCommand::class')
        && str_contains($serviceProvider, 'Commands\\ProjectInstallCommand::class')
        && str_contains($serviceProvider, 'Commands\\ProjectPublishCommand::class'),
    'LartrixServiceProvider must register project make/install/publish commands.'
);
check(
    str_contains($projectInstallCommand, "protected \$signature = 'lartrix:project-install")
        && str_contains($projectInstallCommand, '{--plan= : Existing install-plan.json path}')
        && str_contains($projectInstallCommand, '{--execute : Download, stage, verify and copy missing modules}')
        && str_contains($projectInstallCommand, 'RegistryPackageDownloader')
        && str_contains($projectInstallCommand, 'RegistryStagedPackageInstaller')
        && str_contains($projectInstallCommand, 'writeAudit'),
    'ProjectInstallCommand must support conservative project install execution from registry or local install-plan.'
);
check(
    str_contains($projectInstallPlanStore, 'class ProjectInstallPlanStore')
        && str_contains($projectInstallPlanStore, 'project-config.json')
        && str_contains($projectInstallPlanStore, 'contract-bindings.json')
        && str_contains($projectInstallPlanStore, 'setup.json')
        && str_contains($projectInstallPlanStore, 'moduleConfig')
        && str_contains($moduleController, 'ProjectInstallPlanStore'),
    'Project install plans must be stored with stable project/module config and contract binding artifacts.'
);
$moduleMakeCommand = source('src/Commands/ModuleMakeCommand.php');
$moduleManifestStub = source('stubs/module/module.json.stub');
check(
    str_contains($moduleMakeCommand, '{--author= : Module author')
        && str_contains($moduleMakeCommand, '{--author-url= : Module author URL}')
        && str_contains($moduleMakeCommand, "'{{AUTHOR}}' => \$author")
        && str_contains($moduleManifestStub, '"author": "{{AUTHOR}}"')
        && str_contains($moduleManifestStub, '"author_url": "{{AUTHOR_URL}}"'),
    'ModuleMakeCommand must allow generated standard modules to include publishable author metadata.'
);
$routes = source('routes/api.php');
check(
    str_contains($routes, "market/modules/{id}/install")
        && str_contains($routes, "market/projects/{id}/install")
        && str_contains($routes, "{name}/publish"),
    'Lartrix module routes must expose upload and market install endpoints.'
);
check(
    str_contains($moduleController, "'marketModuleType' => 'all'")
        && str_contains($moduleController, "'marketProjectType' => 'all'")
        && str_contains($moduleController, "['label' => '全部', 'value' => 'all']")
        && str_contains($moduleController, "'language' => 'php', 'framework' => 'laravel'")
        && str_contains($moduleController, 'normalizeMarketType'),
    'ModuleController market modal must default category selects to selected "all" and send php/laravel adapter params.'
);

check(
    str_contains($moduleController, "'marketModulePageSize' => 16")
        && str_contains($moduleController, "'marketProjectPageSize' => 16")
        && str_contains($moduleController, "page_size' => 16")
        && str_contains($moduleController, 'marketCardGrid')
        && str_contains($moduleController, 'marketDetailModal')
        && str_contains($moduleController, "'content-style' => ['height' => '682px'")
        && str_contains($moduleController, "'flex' => '0 0 48px'")
        && str_contains($moduleController, 'type_label'),
    'ModuleController market modal must use card grid, fixed 16-item pagination, internal footer pagination, translated type labels and a detail modal.'
);

$installCommand = source('src/Commands/InstallCommand.php');
$moduleInstallCommand = source('src/Commands/ModuleInstallCommand.php');
check(
    str_contains($installCommand, "'name' => 'system.module'")
        && !str_contains($installCommand, "'path' => '/module'")
        && str_contains($installCommand, "'icon' => 'mdi:book-open'"),
    'InstallCommand default menus must stay aligned with Thinkrix seeded menus.'
);
check(
    str_contains($installCommand, 'authConfigHasEntry')
        && str_contains($installCommand, 'insertAuthConfigEntry')
        && str_contains($installCommand, "'admins' => [")
        && str_contains($installCommand, "'model' => \\Lartrix\\Models\\AdminUser::class"),
    'InstallCommand must robustly fill auth.php providers.admins for the Lartrix admin guard.'
);
check(
    str_contains($moduleInstallCommand, 'protected function registryAuthKey')
        && str_contains($moduleInstallCommand, "config('lartrix.module_registry.auth_key', '')")
        && str_contains($moduleInstallCommand, '->withToken($authKey)')
        && str_contains($moduleInstallCommand, 'RegistryPackageDownloader(signatureKey: $this->registrySignatureKey(), fetcher: $fetcher)'),
    'ModuleInstallCommand registry downloads must pass the configured Auth Key to protected package URLs.'
);

foreach (['zh-CN.php', 'en-US.php', 'zh-CN/lartrix.php', 'en-US/lartrix.php'] as $langFile) {
    $langSource = source('lang/' . $langFile);
    foreach (['system', 'menu', 'placeholder', 'confirm', 'crud', 'admin', 'notification'] as $topKey) {
        check(
            substr_count($langSource, "    '{$topKey}' => [") === 1,
            "{$langFile} must not define duplicate top-level {$topKey} language keys."
        );
    }

    $lang = require $root . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $langFile;
    foreach (['total_orders', 'revenue', 'visit_trend', 'sales_stats', 'copy_api_key', 'copy_success'] as $homeKey) {
        check(isset($lang['home'][$homeKey]), "{$langFile} must define home.{$homeKey}.");
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "core regression checks passed\n";
