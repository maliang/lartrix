<?php

namespace Lartrix\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * 表名
     */
    protected $table = 'admin_settings';

    /**
     * 可批量赋值的属性
     */
    protected $fillable = [
        'group',
        'key',
        'title',
        'type',
        'value',
        'default_value',
        'description',
        'sort',
    ];

    /**
     * 属性类型转换
     */
    protected $casts = [
        'sort' => 'integer',
    ];

    /**
     * 获取设置值（带缓存）
     */
    public static function get(string $key, $default = null)
    {
        $cacheEnabled = config('lartrix.cache.settings.enabled', true);
        $cachePrefix = config('lartrix.cache.settings.prefix', 'lartrix.setting.');
        $cacheLifetime = config('lartrix.cache.settings.lifetime', 3600);

        if ($cacheEnabled) {
            return Cache::remember(
                $cachePrefix . $key,
                $cacheLifetime,
                fn() => static::where('key', $key)->first()?->getTypedValue() ?? $default
            );
        }

        return static::where('key', $key)->first()?->getTypedValue() ?? $default;
    }

    /**
     * 设置值（不存在则创建）
     */
    public static function set(string $key, $value, ?string $group = null): bool
    {
        // 解析 key，支持 group.key 格式
        if (str_contains($key, '.') && $group === null) {
            [$group, $key] = explode('.', $key, 2);
        }

        $setting = static::where('key', $key)->first();

        if (!$setting) {
            // 创建新设置
            $setting = new static([
                'group' => $group ?? 'general',
                'key' => $key,
                'type' => is_array($value) || is_object($value) ? 'json' : (is_bool($value) ? 'boolean' : 'string'),
            ]);
        }

        $setting->value = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
        $result = $setting->save();

        // 清除缓存
        $cachePrefix = config('lartrix.cache.settings.prefix', 'lartrix.setting.');
        Cache::forget($cachePrefix . $key);

        return $result;
    }

    /**
     * 按分组获取设置（返回 key => value 格式）
     */
    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            // 移除 group 前缀
            $key = $setting->key;
            if (str_starts_with($key, $group . '.')) {
                $key = substr($key, strlen($group) + 1);
            }
            $result[$key] = $setting->getTypedValue();
        }

        return $result;
    }

    /**
     * 按分组获取设置（返回完整信息）
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->orderBy('sort')
            ->get()
            ->map(fn($setting) => [
                'key' => $setting->key,
                'title' => $setting->title,
                'type' => $setting->type,
                'value' => $setting->getTypedValue(),
                'default_value' => $setting->getTypedDefaultValue(),
                'description' => $setting->description,
            ])
            ->toArray();
    }

    /**
     * 获取类型化的值
     */
    public function getTypedValue()
    {
        return $this->castValue($this->value, $this->type);
    }

    /**
     * 获取类型化的默认值
     */
    public function getTypedDefaultValue()
    {
        return $this->castValue($this->default_value, $this->type);
    }

    /**
     * 根据类型转换值
     */
    protected function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
