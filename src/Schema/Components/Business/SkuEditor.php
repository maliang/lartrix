<?php

namespace Lartrix\Schema\Components\Business;

use Lartrix\Schema\Components\Component;

/**
 * SkuEditor - SKU 编辑器组件
 *
 * 用于商品多规格 SKU 配置
 *
 * 示例：
 * SkuEditor::make()
 *     ->model('formData.sku')
 *     ->fields([
 *         ['key' => 'price', 'title' => '价格', 'type' => 'number', 'required' => true, 'min' => 0, 'step' => 0.01],
 *         ['key' => 'stock', 'title' => '库存', 'type' => 'number', 'required' => true, 'min' => 0],
 *         ['key' => 'sku_code', 'title' => 'SKU编码', 'type' => 'text'],
 *     ])
 *     ->maxSpecs(3)
 *     ->enableImage(true)
 *     ->uploadUrl('/api/upload')
 */
class SkuEditor extends Component
{
    public function __construct()
    {
        parent::__construct('SkuEditor');
    }

    public static function make(): static
    {
        return new static();
    }

    /**
     * 设置 v-model 绑定
     */
    public function model(string $model): static
    {
        return $this->props(['modelValue' => "{{ $model }}"]);
    }

    /**
     * 设置自定义字段列配置
     *
     * @param array $fields 字段配置数组，每项包含：
     *   - key: string 字段标识
     *   - title: string 列标题
     *   - type: string 输入类型 (number|text|image)
     *   - required: bool 是否必填
     *   - min: number 最小值
     *   - step: number 步长
     *   - placeholder: string 占位文本
     *   - width: number 列宽度
     */
    public function fields(array $fields): static
    {
        return $this->props(['fields' => $fields]);
    }

    /**
     * 设置最大规格组数量
     */
    public function maxSpecs(int $max): static
    {
        return $this->props(['maxSpecs' => $max]);
    }

    /**
     * 设置是否禁用
     */
    public function disabled(bool $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    /**
     * 设置是否显示批量操作栏
     */
    public function showBatch(bool $show = true): static
    {
        return $this->props(['showBatch' => $show]);
    }

    /**
     * 设置是否启用规格值图片
     */
    public function enableImage(bool $enable = true): static
    {
        return $this->props(['enableImage' => $enable]);
    }

    /**
     * 设置图片上传地址
     */
    public function uploadUrl(string $url): static
    {
        return $this->props(['uploadUrl' => $url]);
    }

    /**
     * 设置上传请求头
     */
    public function uploadHeaders(array $headers): static
    {
        return $this->props(['uploadHeaders' => $headers]);
    }

    /**
     * 快捷设置：价格+库存+SKU编码 默认字段
     */
    public function defaultFields(): static
    {
        return $this->fields([
            ['key' => 'price', 'title' => '价格', 'type' => 'number', 'required' => true, 'min' => 0, 'step' => 0.01, 'placeholder' => '0.00'],
            ['key' => 'stock', 'title' => '库存', 'type' => 'number', 'required' => true, 'min' => 0, 'step' => 1, 'placeholder' => '0'],
            ['key' => 'sku_code', 'title' => 'SKU编码', 'type' => 'text', 'placeholder' => '选填'],
        ]);
    }
}
