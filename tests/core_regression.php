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
    str_contains($systemController, 'protected function buildLogoHeader(string $appTitle, string $appSubtitle, string $logo): Flex')
        && !str_contains($systemController, "->props(['src' => \$theme['logo']"),
    'SystemController login logo header must not read an out-of-scope $theme variable.'
);

$authController = source('src/Controllers/AuthController.php');
check(
    str_contains($authController, 'Setting::fetchThemeConfig'),
    'AuthController config must read merged theme config.'
);

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "core regression checks passed\n";
