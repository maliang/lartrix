<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Models\Setting;
use Lartrix\Schema\Components\NaiveUI\Card;
use Lartrix\Schema\Components\NaiveUI\Form;
use Lartrix\Schema\Components\NaiveUI\FormItem;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\SwitchC;
use Lartrix\Schema\Components\NaiveUI\Upload;
use Lartrix\Schema\Components\NaiveUI\Image;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Components\NaiveUI\Space;

class SettingController extends Controller
{
    /**
     * 设置入口（支持 action_type 分发）
     */
    public function index(Request $request): array
    {
        $actionType = $request->input('action_type', 'list');

        return match ($actionType) {
            'form_ui' => $this->formUi(),
            default => $this->list(),
        };
    }

    /**
     * 设置列表
     */
    protected function list(): array
    {
        $settings = Setting::orderBy('group')
            ->orderBy('sort')
            ->get()
            ->groupBy('group')
            ->map(fn($items) => $items->map(fn($item) => [
                'id' => $item->id,
                'key' => $item->key,
                'title' => $item->title,
                'type' => $item->type,
                'value' => $item->getTypedValue(),
                'default_value' => $item->getTypedDefaultValue(),
                'description' => $item->description,
            ])->toArray())
            ->toArray();

        return success($settings);
    }

    /**
     * 按分组获取设置
     */
    public function group(string $group): array
    {
        $settings = Setting::getByGroup($group);
        return success($settings);
    }

    /**
     * 批量更新设置
     */
    public function update(Request $request): array
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
        ]);

        $themeUpdates = [];
        $themeMapping = [
            'login.appTitle' => 'appTitle',
            'login.logo' => 'logo',
            'login.copyright' => 'copyright',
        ];

        foreach ($validated['settings'] as $item) {
            Setting::set($item['key'], $item['value'] ?? '');

            if (array_key_exists($item['key'], $themeMapping)) {
                $themeUpdates[$themeMapping[$item['key']]] = $item['value'] ?? '';
            }
        }

        $this->syncThemeSettings($themeUpdates);

        return success(__t('system.settings_updated'));
    }

    protected function syncThemeSettings(array $themeUpdates): void
    {
        if ($themeUpdates === []) {
            return;
        }

        $theme = Setting::get('theme', config('lartrix.theme', []));
        if (!is_array($theme)) {
            $theme = config('lartrix.theme', []);
        }

        Setting::set('theme', array_merge($theme, $themeUpdates));
    }

    /**
     * 系统设置表单 UI Schema
     */
    protected function formUi(): array
    {
        $uploadAction = '/' . trim((string) config('lartrix.api_prefix', 'api/admin'), '/') . '/upload/image';

        $schema = Card::make()
            ->title(__t('title.system_settings'))
            ->children([
                Form::make()
                    ->props(['model' => '{{ formData }}', 'labelPlacement' => 'left', 'labelWidth' => 120])
                    ->children([
                        FormItem::make()
                            ->label(__t('form.appTitle'))
                            ->path('appTitle')
                            ->children([
                                Input::make()
                                    ->model('formData.appTitle')
                                    ->placeholder(__t('placeholder.appTitle')),
                            ]),
                        FormItem::make()
                            ->label(__t('form.subtitle'))
                            ->path('app_subtitle')
                            ->children([
                                Input::make()
                                    ->model('formData.app_subtitle')
                                    ->placeholder(__t('placeholder.system_subtitle')),
                            ]),
                        FormItem::make()
                            ->label(__t('form.logo_url'))
                            ->path('logo')
                            ->children([
                                Space::make()
                                    ->props(['vertical' => true, 'size' => 'small'])
                                    ->children([
                                        Upload::make()
                                            ->action($uploadAction)
                                            ->accept('.jpg,.jpeg,.png,.gif,.webp,.ico')
                                            // 不用 image-card + max，避免达到上限后触发器被隐藏（导致必须先删除才能再传）；
                                            // 关闭自带文件列表，改用下方 NImage 作为可点击的上传触发区，点击当前 logo 即可重新上传。
                                            ->showFileList(false)
                                            ->props(['name' => 'file'])
                                            ->on('finish', [
                                                // Naive UI 的 onFinish 回调中 file 对象没有 response 字段，
                                                // 上传返回数据只能从 XHR 事件读取：$event.event.target.response（原始 JSON 字符串），需 JSON.parse 解析。
                                                ['set' => 'formData.logo', 'value' => '{{ JSON.parse($event.event.target.response)?.data?.url || "" }}'],
                                                ['call' => '$methods.$message.success', 'args' => [__t('upload.ok')]],
                                            ])
                                            ->on('error', [
                                                ['call' => '$methods.$message.error', 'args' => [__t('upload.failed')]],
                                            ])
                                            ->children([
                                                // 已有 logo：直接点击图片即可重新选图上传（previewDisabled 确保点击冒泡到上传触发器，而非打开预览）
                                                Image::make()
                                                    ->if('formData.logo')
                                                    ->src('{{ formData.logo }}')
                                                    ->width(100)
                                                    ->height(100)
                                                    ->objectFit('contain')
                                                    ->previewDisabled()
                                                    ->props(['style' => 'cursor: pointer; display: block; border: 1px dashed #d9d9d9; border-radius: 6px; padding: 4px;']),
                                                // 无 logo：显示选择按钮
                                                Button::make()->if('!formData.logo')->children([__t('upload.select_image')]),
                                            ]),
                                    ]),
                            ]),
                        FormItem::make()
                            ->label(__t('form.copyright'))
                            ->path('copyright')
                            ->children([
                                Input::make()
                                    ->model('formData.copyright')
                                    ->placeholder(__t('placeholder.copyright')),
                            ]),
                        FormItem::make()
                            ->children([
                                Space::make()
                                    ->children([
                                        Button::make()
                                            ->type('primary')
                                            ->children([__t('button.save_settings')])
                                            ->on('click', [
                                                'fetch' => '/settings',
                                                'method' => 'PUT',
                                                'body' => [
                                                    'settings' => [
                                                        ['key' => 'login.appTitle', 'value' => '{{ formData.appTitle }}'],
                                                        ['key' => 'login.app_subtitle', 'value' => '{{ formData.app_subtitle }}'],
                                                        ['key' => 'login.logo', 'value' => '{{ formData.logo }}'],
                                                        ['key' => 'login.copyright', 'value' => '{{ formData.copyright }}'],
                                                    ],
                                                ],
                                                'then' => [
                                                    ['call' => '$methods.$message.success', 'args' => [__t('common.save_ok')]],
                                                ],
                                            ]),
                                    ]),
                            ]),
                    ]),
            ])
            ->toArray();

        // 将 formData 合并到 schema 的 data 中
        $theme = \Lartrix\Models\Setting::fetchThemeConfig(config('lartrix.theme', []));
        $schema['data'] = [
            'formData' => [
                'appTitle' => $theme['appTitle'] ?? 'Lartrix Admin',
                'app_subtitle' => config('lartrix.app_subtitle', __t('system.default_subtitle')),
                'logo' => $theme['logo'] ?? '',
                'copyright' => config('lartrix.copyright', '© ' . date('Y') . ' Lartrix Admin. All rights reserved.'),
            ],
        ];

        return success($schema);
    }
}
