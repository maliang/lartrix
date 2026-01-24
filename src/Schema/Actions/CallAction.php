<?php

namespace Lartrix\Schema\Actions;

/**
 * CallAction - 调用方法动作
 * 
 * 对应 vschema 的 CallAction 类型
 */
class CallAction implements ActionInterface
{
    public function __construct(
        protected string $method,
        protected array $args = []
    ) {}

    /**
     * 创建实例
     */
    public static function make(string $method, array $args = []): static
    {
        return new static($method, $args);
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        $result = ['call' => $this->method];
        
        if (!empty($this->args)) {
            $result['args'] = $this->args;
        }
        
        return $result;
    }
}
