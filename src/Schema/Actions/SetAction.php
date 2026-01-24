<?php

namespace Lartrix\Schema\Actions;

/**
 * SetAction - 设置值动作
 * 
 * 对应 vschema 的 SetAction 类型
 * 
 * 使用方式：
 * - 单个赋值：SetAction::make('name', 'value')
 * - 批量赋值：SetAction::batch(['name' => '', 'age' => 0, 'status' => true])
 */
class SetAction implements ActionInterface
{
    protected ?array $batchData = null;

    public function __construct(
        protected string $path = '',
        protected mixed $value = null
    ) {}

    /**
     * 创建单个赋值实例
     */
    public static function make(string $path, mixed $value): static
    {
        return new static($path, $value);
    }

    /**
     * 创建批量赋值实例
     * 
     * 示例：
     * SetAction::batch([
     *     'formData.name' => '',
     *     'formData.email' => '',
     *     'formData.status' => true,
     * ])
     * 
     * 会转换为多个 SetAction 数组
     * 
     * @param array $data 键值对数组，键为路径，值为要设置的值
     */
    public static function batch(array $data): static
    {
        $instance = new static();
        $instance->batchData = $data;
        return $instance;
    }

    /**
     * 转换为数组
     * 
     * 批量模式返回 SetAction 数组，单个模式返回单个 SetAction
     */
    public function toArray(): array
    {
        // 批量模式：返回多个 SetAction 组成的数组
        if ($this->batchData !== null) {
            $actions = [];
            foreach ($this->batchData as $path => $value) {
                $actions[] = [
                    'set' => $path,
                    'value' => $value,
                ];
            }
            return $actions;
        }

        // 单个模式
        return [
            'set' => $this->path,
            'value' => $this->value,
        ];
    }

    /**
     * 判断是否为批量模式
     */
    public function isBatch(): bool
    {
        return $this->batchData !== null;
    }
}
