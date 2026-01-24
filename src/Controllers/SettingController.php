<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Lartrix\Models\Setting;
use Lartrix\Schema\Components\NaiveUI\NCard;
use Lartrix\Schema\Components\NaiveUI\NForm;
use Lartrix\Schema\Components\NaiveUI\NFormItem;
use Lartrix\Schema\Components\NaiveUI\NInput;
use Lartrix\Schema\Components\NaiveUI\NSwitch;
use Lartrix\Schema\Components\NaiveUI\NButton;
use Lartrix\Schema\Components\NaiveUI\NSpace;
use function Lartrix\Support\success;

class SettingController extends Controller
{
    /**
     * 设置列表
     */
    public function index(): array
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
            'settings.*.key' => 'required|string|exists:admin_settings,key',
            'settings.*.value' => 'nullable',
        ]);

        $cachePrefix = config('lartrix.settings.cache.prefix', 'lartrix.setting.');

        foreach ($validated['settings'] as $item) {
            $setting = Setting::where('key', $item['key'])->first();
            
            if ($setting) {
                // 根据类型处理值
                $value = $item['value'];
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                } elseif (is_bool($value)) {
                    $value = $value ? '1' : '0';
                } else {
                    $value = (string) $value;
                }

                $setting->value = $value;
                $setting->save();

                // 清除缓存
                Cache::forget($cachePrefix . $item['key']);
            }
        }

        return success('更新成功');
    }

    /**
     * 系统设置表单 UI Schema
     */
    public function formUi(): array
    {
        $schema = NCard::make()
            ->title('系统设置')
            ->children([
                NForm::make()
                    ->props(['model' => '{{ formData }}', 'labelPlacement' => 'left', 'labelWidth' => 120])
                    ->children([
                        NFormItem::make()
                            ->label('系统名称')
                            ->path('app_title')
                            ->children([
                                NInput::make()
                                    ->model('formData.app_title')
                                    ->placeholder('请输入系统名称'),
                            ]),
                        NFormItem::make()
                            ->label('系统副标题')
                            ->path('app_subtitle')
                            ->children([
                                NInput::make()
                                    ->model('formData.app_subtitle')
                                    ->placeholder('请输入系统副标题'),
                            ]),
                        NFormItem::make()
                            ->label('Logo 地址')
                            ->path('logo')
                            ->children([
                                NInput::make()
                                    ->model('formData.logo')
                                    ->placeholder('请输入 Logo 地址'),
                            ]),
                        NFormItem::make()
                            ->label('版权信息')
                            ->path('copyright')
                            ->children([
                                NInput::make()
                                    ->model('formData.copyright')
                                    ->placeholder('请输入版权信息'),
                            ]),
                        NFormItem::make()
                            ->children([
                                NSpace::make()
                                    ->children([
                                        NButton::make()
                                            ->type('primary')
                                            ->children(['保存设置'])
                                            ->on('click', [
                                                'action' => 'request',
                                                'url' => '/settings',
                                                'method' => 'PUT',
                                                'data' => [
                                                    'settings' => [
                                                        ['key' => 'login.app_title', 'value' => '{{ formData.app_title }}'],
                                                        ['key' => 'login.app_subtitle', 'value' => '{{ formData.app_subtitle }}'],
                                                        ['key' => 'login.logo', 'value' => '{{ formData.logo }}'],
                                                        ['key' => 'login.copyright', 'value' => '{{ formData.copyright }}'],
                                                    ],
                                                ],
                                                'successMessage' => '保存成功',
                                            ]),
                                    ]),
                            ]),
                    ]),
            ])
            ->toArray();

        // 将 formData 合并到 schema 的 data 中
        $schema['data'] = [
            'formData' => [
                'app_title' => config('lartrix.app_title', 'Lartrix Admin'),
                'app_subtitle' => config('lartrix.app_subtitle', 'JSON 驱动的后台管理系统'),
                'logo' => config('lartrix.logo', '/admin/favicon.svg'),
                'copyright' => config('lartrix.copyright', '© ' . date('Y') . ' Lartrix Admin. All rights reserved.'),
            ],
        ];

        return success($schema);
    }
}
