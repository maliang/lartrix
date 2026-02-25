<?php

namespace Lartrix\Services;

use Illuminate\Support\Facades\Cache;
use Lartrix\Models\DictGroup;
use Lartrix\Models\DictItem;

class DataDictService
{
    /**
     * 缓存前缀
     */
    protected string $cachePrefix = 'lartrix.dict.';

    /**
     * 缓存时间（秒）
     */
    protected int $cacheLifetime = 3600;

    /**
     * 获取字典项列表
     *
     * @param string $groupCode 分组代码
     * @param bool $enabledOnly 是否只返回启用的项
     * @return array
     */
    public function get(string $groupCode, bool $enabledOnly = true): array
    {
        $cacheKey = $this->cachePrefix . $groupCode . ($enabledOnly ? '.enabled' : '.all');

        return Cache::remember($cacheKey, $this->cacheLifetime, function () use ($groupCode, $enabledOnly) {
            $group = DictGroup::findByCode($groupCode);
            if (!$group) {
                return [];
            }

            $query = $group->items();
            if ($enabledOnly) {
                $query->enabled();
            }

            return $query->get()->map(fn($item) => [
                'code' => $item->code,
                'label' => $item->label,
                'value' => $item->value,
                'extra' => $item->extra,
            ])->toArray();
        });
    }

    /**
     * 获取单个字典项的 label
     *
     * @param string $groupCode 分组代码
     * @param string $itemCode 项代码
     * @return string|null
     */
    public function getLabel(string $groupCode, string $itemCode): ?string
    {
        $items = $this->get($groupCode);
        foreach ($items as $item) {
            if ($item['code'] === $itemCode || $item['value'] === $itemCode) {
                return $item['label'];
            }
        }
        return null;
    }

    /**
     * 获取字典选项（value => label 格式）
     *
     * @param string $groupCode 分组代码
     * @return array
     */
    public function options(string $groupCode): array
    {
        $items = $this->get($groupCode);
        $options = [];
        foreach ($items as $item) {
            $options[$item['value']] = $item['label'];
        }
        return $options;
    }

    /**
     * 获取字典选项（用于前端 select 组件）
     *
     * @param string $groupCode 分组代码
     * @return array [['label' => '...', 'value' => '...'], ...]
     */
    public function selectOptions(string $groupCode): array
    {
        $items = $this->get($groupCode);
        return array_map(fn($item) => [
            'label' => $item['label'],
            'value' => $item['value'],
        ], $items);
    }

    /**
     * 清除指定分组的缓存
     *
     * @param string $groupCode 分组代码
     */
    public function clearCache(string $groupCode): void
    {
        Cache::forget($this->cachePrefix . $groupCode . '.enabled');
        Cache::forget($this->cachePrefix . $groupCode . '.all');
    }

    /**
     * 清除所有字典缓存
     */
    public function clearAllCache(): void
    {
        // 获取所有分组并清除缓存
        $groups = DictGroup::all();
        foreach ($groups as $group) {
            $this->clearCache($group->code);
        }
    }
}
